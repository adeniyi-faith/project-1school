<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Attendance;
use App\Models\BehaviourRating;
use App\Models\ReportCardComment;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Gathers everything printed on a report card from the stored term results:
 * the term card (part scores, totals, grades, positions, behaviour, attendance, comments)
 * and the full-year card (each term's total per subject, the year average and position).
 * The PDF views in resources/views/report-cards only lay this data out.
 */
class ReportCardService
{
    /** Sheets parents and students may see */
    public const RELEASED = ['published', 'locked'];

    /** One term card per student on the sheet (only students with results), in class-list order. */
    public function termCards(ResultSheet $sheet, ?array $studentIds = null): array
    {
        $sheet->loadMissing(['term.academicYear', 'schoolClass']);
        $summaries = TermResultSummary::where('result_sheet_id', $sheet->id)
            ->when($studentIds !== null, fn ($q) => $q->whereIn('student_id', $studentIds))
            ->with(['student.section:id,name'])
            ->get()
            ->filter(fn ($s) => $s->student)
            ->sortBy(fn ($s) => $s->student->full_name)
            ->values();
        if ($summaries->isEmpty()) {
            return [];
        }

        $ids = $summaries->pluck('student_id');
        $results = TermResult::where('result_sheet_id', $sheet->id)->whereIn('student_id', $ids)->get()->groupBy('student_id');
        $subjects = Subject::withTrashed()->with('schoolClass:id,assessment_scheme_id')
            ->whereIn('id', $results->flatten()->pluck('subject_id')->unique())->get()->keyBy('id');
        $scores = SubjectScore::where('result_sheet_id', $sheet->id)->whereIn('student_id', $ids)->with('component')->get()
            ->groupBy(fn ($s) => $s->student_id.'-'.$s->subject_id);
        $ratings = BehaviourRating::where('result_sheet_id', $sheet->id)->whereIn('student_id', $ids)
            ->with(['behaviourTrait' => fn ($q) => $q->withTrashed()])->get()->groupBy('student_id');
        $comments = ReportCardComment::where('result_sheet_id', $sheet->id)->whereIn('student_id', $ids)->get()->keyBy('student_id');
        $attendance = $this->attendance($sheet->term, $ids->all());
        $columns = $this->columns($subjects);
        $grading = GradingService::forClass($sheet->school_id, $sheet->class_id);
        $common = $this->common($sheet->school_id, $sheet, $grading);
        $nextTerm = $this->nextTermStart($sheet->term);

        return $summaries->map(function (TermResultSummary $summary) use ($sheet, $results, $subjects, $scores, $ratings, $comments, $attendance, $columns, $grading, $common, $nextTerm) {
            $student = $summary->student;
            $rows = ($results[$student->id] ?? collect())
                ->sortBy(fn ($r) => $subjects[$r->subject_id]?->name)
                ->map(function (TermResult $r) use ($student, $subjects, $scores) {
                    $parts = ($scores[$student->id.'-'.$r->subject_id] ?? collect())
                        ->mapWithKeys(fn ($s) => [$s->component?->short_name ?? '?' => $s->score]);

                    return [
                        'subject' => $subjects[$r->subject_id]?->name ?? 'Removed subject',
                        'parts' => $parts->all(),
                        'total' => $r->total,
                        'grade' => $r->grade,
                        'remarks' => $r->remarks,
                        'position' => $r->subject_position,
                        'average' => $r->subject_average,
                        'highest' => $r->subject_highest,
                        'lowest' => $r->subject_lowest,
                    ];
                })->values()->all();
            $comment = $comments[$student->id] ?? null;

            return $common + [
                'student' => $this->studentInfo($student, $sheet->schoolClass?->name),
                'columns' => $columns,
                'rows' => $rows,
                'summary' => [
                    'subjects' => $summary->subjects_count,
                    'total' => $summary->total_score,
                    'average' => $summary->average,
                    'grade' => $grading->calculate((float) $summary->average, 100)['grade'],
                    'position' => $summary->position,
                    'class_size' => $summary->class_size,
                    'class_average' => $sheet->class_average,
                ],
                'ratings' => ($ratings[$student->id] ?? collect())
                    ->sortBy(fn ($r) => [$r->behaviourTrait?->domain, $r->behaviourTrait?->sort_order])
                    ->groupBy(fn ($r) => $r->behaviourTrait?->domain ?? 'affective')
                    ->map(fn ($group) => $group->map(fn ($r) => ['name' => $r->behaviourTrait?->name, 'rating' => $r->rating])->values()->all())
                    ->all(),
                'attendance' => $attendance[$student->id] ?? null,
                'teacher_comment' => $comment?->teacher_comment,
                'principal_comment' => $comment?->principal_comment,
                'next_term_begins' => $nextTerm,
            ];
        })->all();
    }

    /**
     * Full-year cards for the students on a class's sheets in one school year: each term's total
     * per subject, the year average per subject, the overall year average and position, and the
     * end-of-year decision (moved up, repeating, graduated) once it has been made.
     */
    public function sessionCards(AcademicYear $year, int $classId, ?array $studentIds = null, bool $releasedOnly = false): array
    {
        $terms = $year->terms()->get();
        $sheets = ResultSheet::where('class_id', $classId)->whereIn('term_id', $terms->pluck('id'))
            ->when($releasedOnly, fn ($q) => $q->whereIn('status', self::RELEASED))
            ->with('schoolClass')->get()
            ->sortBy(fn ($s) => $terms->firstWhere('id', $s->term_id)?->sequence)->values();
        if ($sheets->isEmpty()) {
            return [];
        }

        $summaries = TermResultSummary::whereIn('result_sheet_id', $sheets->pluck('id'))->get();
        // The year average is the mean of a student's term averages; everyone in the class is ranked on it
        $yearAverages = $summaries->groupBy('student_id')->map(fn ($rows) => round($rows->avg('average'), 2));
        $classSize = $yearAverages->count();

        $ids = collect($studentIds ?? $yearAverages->keys())->intersect($yearAverages->keys())->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $students = Student::whereIn('id', $ids)->with('section:id,name')->get()->sortBy(fn ($s) => $s->full_name)->values();
        $results = TermResult::whereIn('result_sheet_id', $sheets->pluck('id'))->whereIn('student_id', $ids)->get()->groupBy('student_id');
        $subjects = Subject::withTrashed()->whereIn('id', $results->flatten()->pluck('subject_id')->unique())->pluck('name', 'id');
        $decisions = StudentPromotion::whereIn('student_id', $ids)
            ->whereHas('batch', fn ($q) => $q->where('academic_year_id', $year->id)->whereNull('undone_at'))
            ->with(['toClass' => fn ($q) => $q->withTrashed(), 'fromClass' => fn ($q) => $q->withTrashed()])
            ->get()->keyBy('student_id');

        $class = $sheets->first()->schoolClass;
        $grading = GradingService::forClass($year->school_id, $classId);
        $common = $this->common($year->school_id, $sheets->last(), $grading, $sheets);
        $termNames = $sheets->map(fn ($s) => $terms->firstWhere('id', $s->term_id)?->name)->all();

        return $students->map(function (Student $student) use ($sheets, $summaries, $results, $subjects, $yearAverages, $classSize, $decisions, $class, $grading, $common, $termNames, $year) {
            $mine = $results[$student->id] ?? collect();
            $rows = $mine->groupBy('subject_id')->map(function ($perSubject, $subjectId) use ($sheets, $subjects, $grading) {
                $byTerm = $sheets->map(fn ($sheet) => $perSubject->firstWhere('result_sheet_id', $sheet->id)?->total)->all();
                $taken = array_filter($byTerm, fn ($t) => $t !== null);
                $average = $taken ? round(array_sum($taken) / count($taken), 2) : null;

                return [
                    'subject' => $subjects[$subjectId] ?? 'Removed subject',
                    'terms' => $byTerm,
                    'average' => $average,
                    'grade' => $average === null ? null : $grading->calculate($average, 100)['grade'],
                ];
            })->sortBy('subject')->values()->all();

            $termSummaries = $sheets->map(fn ($sheet) => $summaries->first(fn ($s) => $s->result_sheet_id === $sheet->id && $s->student_id === $student->id));
            $average = $yearAverages[$student->id];
            $decision = $decisions[$student->id] ?? null;

            return $common + [
                'year' => $year->name,
                'student' => $this->studentInfo($student, $class?->name),
                'term_names' => $termNames,
                'rows' => $rows,
                'term_averages' => $termSummaries->map(fn ($s) => $s?->average)->all(),
                'term_positions' => $termSummaries->map(fn ($s) => $s ? ['position' => $s->position, 'class_size' => $s->class_size] : null)->all(),
                'summary' => [
                    'average' => $average,
                    'grade' => $grading->calculate($average, 100)['grade'],
                    'position' => TermResultService::position($average, $yearAverages->values()),
                    'class_size' => $classSize,
                ],
                'decision' => $decision ? match ($decision->outcome) {
                    'promoted' => 'Promoted to '.($decision->toClass?->name ?? 'the next class'),
                    'repeated' => 'To repeat '.($decision->fromClass?->name ?? 'the class'),
                    'graduated' => 'Graduated',
                    default => null,
                } : null,
            ];
        })->all();
    }

    /** School details, grade key and the "not yet published" flag shared by every card in a batch. */
    private function common(int $schoolId, ResultSheet $sheet, GradingService $grading, ?Collection $sheets = null): array
    {
        $school = School::find($schoolId);
        $sheet->loadMissing('term.academicYear');

        return [
            'school' => [
                'name' => $school?->name,
                'address' => collect([$school?->address, $school?->city, $school?->state])->filter()->implode(', '),
                'phone' => $school?->phone,
                'email' => $school?->email,
                'motto' => SchoolSetting::get($schoolId, 'tagline'),
                'logo' => $this->logo($school),
            ],
            'term' => $sheet->term?->name,
            'session' => $sheet->term?->academicYear?->name,
            'grade_key' => $grading->scales()->map(fn ($s) => ['grade' => $s->grade, 'min' => (float) $s->min_marks, 'max' => (float) $s->max_marks, 'remarks' => $s->remarks])->values()->all(),
            'preview' => ($sheets ?? collect([$sheet]))->contains(fn ($s) => ! in_array($s->status, self::RELEASED, true)),
        ];
    }

    private function studentInfo(Student $student, ?string $className): array
    {
        return [
            'name' => $student->full_name,
            'admission_no' => $student->admission_no,
            'class' => $className,
            'section' => $student->section?->name,
            'gender' => $student->gender ? ucfirst($student->gender) : null,
            'age' => $student->date_of_birth?->age,
            'photo' => null,
        ];
    }

    /** Score part headings (CA1, CA2, Exam ...) across the subjects on the card, in order. */
    private function columns(Collection $subjects): array
    {
        $schemes = [];
        foreach ($subjects as $subject) {
            $scheme = AssessmentScheme::forSubject($subject);
            if ($scheme && ! isset($schemes[$scheme->id])) {
                $schemes[$scheme->id] = $scheme->components()->get(['short_name', 'max_score']);
            }
        }

        return collect($schemes)->flatten(1)
            ->unique('short_name')
            ->map(fn ($c) => ['name' => $c->short_name, 'max' => $c->max_score])
            ->values()->all();
    }

    /** student_id => days present (late counts as present), absent, and days marked, within the term's dates */
    private function attendance(?Term $term, array $studentIds): array
    {
        if (! $term?->start_date || ! $term?->end_date) {
            return [];
        }

        return Attendance::where('attendable_type', Student::class)
            ->whereIn('attendable_id', $studentIds)
            ->whereBetween('date', [$term->start_date->toDateString(), $term->end_date->toDateString()])
            ->get(['attendable_id', 'status'])
            ->groupBy('attendable_id')
            ->map(fn ($rows) => [
                'present' => $rows->whereIn('status', ['present', 'late'])->count() + 0.5 * $rows->where('status', 'half_day')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'marked' => $rows->count(),
            ])->all();
    }

    /** When the next term starts: the next term this year, else the first term of the following year. */
    private function nextTermStart(?Term $term): ?string
    {
        if (! $term) {
            return null;
        }

        $next = Term::where('academic_year_id', $term->academic_year_id)->where('sequence', '>', $term->sequence)->orderBy('sequence')->first();
        if (! $next && $term->academicYear?->start_date) {
            $nextYear = AcademicYear::where('school_id', $term->school_id)->where('start_date', '>', $term->academicYear->start_date)->orderBy('start_date')->first();
            $next = $nextYear?->terms()->first();
        }

        return $next?->start_date?->format('j F Y');
    }

    /** The school logo as an embedded image, so the PDF does not need to fetch anything */
    private function logo(?School $school): ?string
    {
        if (! $school?->logo) {
            return null;
        }
        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($school->logo)) {
                return null;
            }

            return 'data:'.($disk->mimeType($school->logo) ?: 'image/png').';base64,'.base64_encode($disk->get($school->logo));
        } catch (Throwable) {
            return null;
        }
    }
}
