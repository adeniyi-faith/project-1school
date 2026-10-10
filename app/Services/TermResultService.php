<?php

namespace App\Services;

use App\Models\BehaviourRating;
use App\Models\ResultSheet;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Works out a class's term results from the part scores (CA1 + CA2 + Exam ...)
 * and stores them: each subject total and grade, subject position, subject
 * average/highest/lowest, and each student's overall average and class position.
 *
 * Run it whenever a sheet's scores change. Pages then read the stored results
 * instead of adding everything up on every view.
 */
class TermResultService
{
    public function compute(ResultSheet $sheet): void
    {
        $grading = GradingService::forClass($sheet->school_id, $sheet->class_id);

        // Subject total = the sum of its part scores (the parts add up to 100)
        $totals = DB::table('subject_scores')
            ->where('result_sheet_id', $sheet->id)
            ->whereNotNull('score')
            ->groupBy('student_id', 'subject_id')
            ->select('student_id', 'subject_id', DB::raw('SUM(score) as total'))
            ->get()
            ->map(fn ($r) => (object) ['student_id' => (int) $r->student_id, 'subject_id' => (int) $r->subject_id, 'total' => round((float) $r->total, 2)]);

        DB::transaction(function () use ($sheet, $grading, $totals) {
            $version = $sheet->version + 1;
            $now = now();

            DB::table('term_results')->where('result_sheet_id', $sheet->id)->delete();
            DB::table('term_result_summaries')->where('result_sheet_id', $sheet->id)->delete();

            $rows = [];
            foreach ($totals->groupBy('subject_id') as $subjectId => $subjectRows) {
                $scores = $subjectRows->pluck('total');
                $average = round($scores->avg(), 2);
                foreach ($subjectRows as $r) {
                    $graded = $grading->calculate($r->total, 100);
                    $rows[] = [
                        'school_id' => $sheet->school_id, 'result_sheet_id' => $sheet->id,
                        'student_id' => $r->student_id, 'subject_id' => $subjectId,
                        'total' => $r->total, 'grade' => $graded['grade'], 'gpa' => $graded['gpa'], 'remarks' => $graded['remarks'],
                        'subject_position' => self::position($r->total, $scores),
                        'subject_average' => $average, 'subject_highest' => $scores->max(), 'subject_lowest' => $scores->min(),
                        'version' => $version, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('term_results')->insert($chunk);
            }

            $students = $totals->groupBy('student_id')->map(fn (Collection $r) => [
                'count' => $r->count(),
                'total' => round($r->sum('total'), 2),
                'average' => round($r->avg('total'), 2),
            ]);
            $averages = $students->pluck('average');

            $summaries = $students->map(fn ($s, $studentId) => [
                'school_id' => $sheet->school_id, 'result_sheet_id' => $sheet->id, 'student_id' => $studentId,
                'subjects_count' => $s['count'], 'total_score' => $s['total'], 'average' => $s['average'],
                'position' => self::position($s['average'], $averages), 'class_size' => $students->count(),
                'version' => $version, 'created_at' => $now, 'updated_at' => $now,
            ])->values()->all();
            foreach (array_chunk($summaries, 500) as $chunk) {
                DB::table('term_result_summaries')->insert($chunk);
            }

            $sheet->forceFill([
                'version' => $version,
                'class_average' => $averages->isEmpty() ? null : round($averages->avg(), 2),
                'computed_at' => $now,
            ])->save();
        });
    }

    /**
     * A student's published term results, newest first, for the student and
     * parent portals. Results that are not published yet are left out.
     */
    public function familyReports(Student $student): array
    {
        $summaries = TermResultSummary::withoutGlobalScopes()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereHas('sheet', fn ($q) => $q->withoutGlobalScopes()->whereIn('status', ['published', 'locked']))
            ->with(['sheet' => fn ($q) => $q->withoutGlobalScopes()->with([
                'term' => fn ($t) => $t->withoutGlobalScopes()->with(['academicYear' => fn ($y) => $y->withoutGlobalScopes()]),
                'schoolClass' => fn ($c) => $c->withoutGlobalScopes(),
            ])])
            ->get()
            ->sortByDesc(fn ($s) => [(string) $s->sheet->term?->academicYear?->start_date, $s->sheet->term?->sequence])
            ->values();

        $sheetIds = $summaries->pluck('result_sheet_id');
        $results = TermResult::withoutGlobalScopes()->whereIn('result_sheet_id', $sheetIds)->where('student_id', $student->id)
            ->with(['subject' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'name')])->get()->groupBy('result_sheet_id');
        $ratings = BehaviourRating::withoutGlobalScopes()->whereIn('result_sheet_id', $sheetIds)->where('student_id', $student->id)
            ->with(['behaviourTrait' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])->get()->groupBy('result_sheet_id');

        return $summaries->map(fn (TermResultSummary $s) => [
            'id' => $s->result_sheet_id,
            'term' => trim(($s->sheet->term?->academicYear?->name ?? '') . ' · ' . $s->sheet->term?->name, ' ·'),
            'class' => $s->sheet->schoolClass?->name,
            'average' => $s->average,
            'total_score' => $s->total_score,
            'position' => $s->position,
            'class_size' => $s->class_size,
            'class_average' => $s->sheet->class_average,
            'subjects' => ($results[$s->result_sheet_id] ?? collect())->sortBy(fn ($r) => $r->subject?->name)->map(fn (TermResult $r) => [
                'subject' => $r->subject?->name, 'total' => $r->total, 'grade' => $r->grade, 'remarks' => $r->remarks,
                'position' => $r->subject_position, 'average' => $r->subject_average,
            ])->values(),
            'ratings' => ($ratings[$s->result_sheet_id] ?? collect())->map(fn (BehaviourRating $r) => [
                'name' => $r->behaviourTrait?->name, 'domain' => $r->behaviourTrait?->domain,
                'rating' => $r->rating, 'label' => BehaviourRating::LABELS[$r->rating] ?? null,
            ])->values(),
        ])->all();
    }

    /** Position among the scores, where equal scores share a place: 1, 2, 2, 4. */
    public static function position(float $score, Collection $scores): int
    {
        return 1 + $scores->filter(fn ($s) => round((float) $s, 2) > round($score, 2))->count();
    }
}
