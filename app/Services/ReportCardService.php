<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\Attendance;
use App\Models\BehaviourRating;
use App\Models\Invoice;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
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
use App\Support\EmbeddedImage;

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
        $remarks = ReportCardRemark::where('result_sheet_id', $sheet->id)->whereIn('student_id', $ids)->get()->groupBy('student_id');
        $attendance = $this->attendance($sheet->term, $ids->all());
        $columns = $this->columns($subjects);
        $grading = GradingService::forClass($sheet->school_id, $sheet->class_id);
        $design = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id);
        $common = $this->common($sheet->school_id, $sheet, $grading, $design);
        $nextTerm = $this->nextTermStart($sheet->term);
        $owed = $common['design']['show_fees_owed'] ? $this->feesOwed($ids->all()) : [];

        return $summaries->map(function (TermResultSummary $summary) use ($sheet, $results, $subjects, $scores, $ratings, $remarks, $attendance, $columns, $grading, $common, $nextTerm, $owed) {
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
            return $common + [
                'student' => $this->studentInfo($student, $sheet->schoolClass?->name, $common['design']['show_photo']),
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
                // signer id => comment
                'remarks' => ($remarks[$student->id] ?? collect())->pluck('comment', 'report_card_signer_id')->all(),
                'next_term_begins' => $nextTerm,
                'fees_owed' => $owed[$student->id] ?? 0.0,
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
        $common = $this->common($year->school_id, $sheets->last(), $grading, ReportCardDesign::forClass($year->school_id, $classId), $sheets);
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
                'student' => $this->studentInfo($student, $class?->name, $common['design']['show_photo']),
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
    private function common(int $schoolId, ResultSheet $sheet, GradingService $grading, ReportCardDesign $design, ?Collection $sheets = null): array
    {
        $sheet->loadMissing('term.academicYear');

        return [
            'school' => $this->school($schoolId),
            'design' => $this->design($design),
            'term' => $sheet->term?->name,
            'session' => $sheet->term?->academicYear?->name,
            'grade_key' => $this->gradeKey($grading),
            'preview' => ($sheets ?? collect([$sheet]))->contains(fn ($s) => ! in_array($s->status, self::RELEASED, true)),
        ];
    }

    /**
     * A made-up student's cards, printed with a design, so a school can see how the design
     * looks before any results exist. Uses the school's own header, grade scale and signers.
     */
    public function sampleCards(ReportCardDesign $design, string $type = 'term'): array
    {
        $grading = new GradingService($design->school_id);
        $common = [
            'school' => $this->school($design->school_id),
            'design' => $this->design($design),
            'term' => 'First Term',
            'session' => now()->year.'/'.(now()->year + 1),
            'grade_key' => $this->gradeKey($grading),
            'preview' => false,
        ];
        $student = ['name' => 'Adaeze Okafor (sample)', 'admission_no' => 'ADM-0000-0001', 'class' => 'JSS 2', 'section' => 'A', 'gender' => 'Female', 'age' => 12, 'photo' => null];
        $subjects = ['English Language' => [16, 15, 48], 'Mathematics' => [18, 17, 52], 'Basic Science' => [14, 13, 41], 'Social Studies' => [17, 16, 45], 'Civic Education' => [12, 14, 38], 'Computer Studies' => [19, 18, 55]];

        if ($type === 'session') {
            $rows = [];
            foreach ($subjects as $name => [$a, $b, $c]) {
                $terms = [$a + $b + $c, min(100, $a + $b + $c + 4), min(100, $a + $b + $c - 3)];
                $avg = round(array_sum($terms) / 3, 2);
                $rows[] = ['subject' => $name, 'terms' => $terms, 'average' => $avg, 'grade' => $grading->calculate($avg, 100)['grade']];
            }
            $average = round(collect($rows)->avg('average'), 2);

            return [$common + [
                'year' => $common['session'], 'student' => $student, 'term_names' => ['First Term', 'Second Term', 'Third Term'],
                'rows' => $rows, 'term_averages' => [74.5, 77.17, 72.17],
                'term_positions' => [['position' => 4, 'class_size' => 32], ['position' => 3, 'class_size' => 32], ['position' => 5, 'class_size' => 31]],
                'summary' => ['average' => $average, 'grade' => $grading->calculate($average, 100)['grade'], 'position' => 4, 'class_size' => 32],
                'decision' => 'Promoted to JSS 3',
            ]];
        }

        $rows = [];
        foreach ($subjects as $name => [$a, $b, $c]) {
            $total = $a + $b + $c;
            $graded = $grading->calculate($total, 100);
            $rows[] = ['subject' => $name, 'parts' => ['CA1' => $a, 'CA2' => $b, 'Exam' => $c], 'total' => $total, 'grade' => $graded['grade'],
                'remarks' => $graded['remarks'], 'position' => ($total % 7) + 1, 'average' => $total - 9.5, 'highest' => min(100, $total + 8), 'lowest' => $total - 31];
        }
        $total = array_sum(array_column($rows, 'total'));
        $average = round($total / count($rows), 2);
        $traits = ['affective' => ['Punctuality', 'Neatness', 'Politeness', 'Honesty'], 'psychomotor' => ['Handwriting', 'Sports and games', 'Drawing and painting']];
        $samples = ['A hardworking pupil who takes part well in class. Keep it up.', 'A good result. Aim higher in Civic Education next term.', 'Well done.', 'Keep it up.'];

        return [$common + [
            'student' => $student,
            'columns' => [['name' => 'CA1', 'max' => 20], ['name' => 'CA2', 'max' => 20], ['name' => 'Exam', 'max' => 60]],
            'rows' => $rows,
            'summary' => ['subjects' => count($rows), 'total' => $total, 'average' => $average, 'grade' => $grading->calculate($average, 100)['grade'],
                'position' => 4, 'class_size' => 32, 'class_average' => 63.4],
            'ratings' => collect($traits)->map(fn ($names) => collect($names)->map(fn ($n, $i) => ['name' => $n, 'rating' => 5 - ($i % 3)])->all())->all(),
            'attendance' => ['present' => 58, 'absent' => 2, 'marked' => 60],
            'remarks' => collect($common['design']['signers'])->filter(fn ($s) => $s['has_comment'])->values()
                ->mapWithKeys(fn ($s, $i) => [$s['id'] => $samples[$i] ?? 'Well done.'])->all(),
            'next_term_begins' => now()->addMonths(3)->format('j F Y'),
            'fees_owed' => 45000.0,
        ]];
    }

    /** School name, contact lines, motto and logo for the card header */
    public function school(int $schoolId): array
    {
        $school = School::find($schoolId);

        return [
            'name' => $school?->name,
            'address' => collect([$school?->address, $school?->city, $school?->state])->filter()->implode(', '),
            'phone' => $school?->phone,
            'email' => $school?->email,
            'motto' => SchoolSetting::get($schoolId, 'tagline'),
            'logo' => EmbeddedImage::from($school?->logo, 'public'),
        ];
    }

    /** Everything the PDF views need from a design: switches, colours, sizes, titles, signers and images */
    public function design(ReportCardDesign $design): array
    {
        $design->loadMissing('signers');

        return $design->settings() + [
            'template' => in_array($design->template, ReportCardDesign::TEMPLATES, true) ? $design->template : 'classic',
            'primary' => $design->primary_color ?: '#312e81',
            'accent' => $design->accent_color ?: '#4f46e5',
            'font_size' => ['small' => 9, 'normal' => 10, 'large' => 11][$design->font_size] ?? 10,
            'paper' => $design->paper === 'letter' ? 'letter' : 'a4',
            'term_title' => $design->term_title ?: 'Report Card',
            'session_title' => $design->session_title ?: 'Full-Year Report Card',
            'footer_note' => $design->footer_note,
            'stamp' => EmbeddedImage::from($design->stamp_path),
            'signers' => $design->signers->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'name' => $s->name,
                'has_comment' => $s->has_comment,
                'signature' => EmbeddedImage::from($s->signature_path),
            ])->all(),
        ];
    }

    public function gradeKey(GradingService $grading): array
    {
        return $grading->scales()->map(fn ($s) => ['grade' => $s->grade, 'min' => (float) $s->min_marks, 'max' => (float) $s->max_marks, 'remarks' => $s->remarks])->values()->all();
    }

    private function studentInfo(Student $student, ?string $className, bool $withPhoto = false): array
    {
        return [
            'name' => $student->full_name,
            'admission_no' => $student->admission_no,
            'class' => $className,
            'section' => $student->section?->name,
            'gender' => $student->gender ? ucfirst($student->gender) : null,
            'age' => $student->date_of_birth?->age,
            'photo' => $withPhoto ? EmbeddedImage::from($student->photo) : null,
        ];
    }

    /** student_id => what is still owed on their open invoices */
    private function feesOwed(array $studentIds): array
    {
        return Invoice::whereIn('student_id', $studentIds)->whereIn('status', ['unpaid', 'partial'])
            ->groupBy('student_id')->selectRaw('student_id, SUM(balance) as owed')
            ->pluck('owed', 'student_id')->map(fn ($v) => round((float) $v, 2))->all();
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
}
