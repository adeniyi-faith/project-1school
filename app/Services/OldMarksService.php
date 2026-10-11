<?php

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScheme;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ResultSheet;
use App\Models\Subject;
use App\Models\SubjectScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Copies marks from the older one-number-per-exam screen into a score part (CA1, Exam ...) of the term
 * results, so they count on report cards. A mark is scaled to the part: 45 out of 50 into a part worth 60
 * becomes 54. The old marks are left as they are.
 */
class OldMarksService
{
    public function __construct(private TermResultService $results) {}

    /**
     * @param  string  $part  the score part's short name or name, matched in each subject's own score setup
     * @return array{copied: int, kept: int, absent: int, missing_part: list<string>, no_full_marks: list<string>}
     */
    public function copy(Exam $exam, int $termId, string $part, bool $replace): array
    {
        if (! $exam->class_id) {
            throw ValidationException::withMessages(['part' => 'This exam has no class, so its marks cannot be placed.']);
        }
        $sheet = ResultSheet::firstOrCreate(['term_id' => $termId, 'class_id' => $exam->class_id], ['school_id' => $exam->school_id, 'status' => 'draft']);
        if (! $sheet->isEditable()) {
            throw ValidationException::withMessages(['part' => 'These term results have been submitted, so scores cannot be added. Ask for them to be sent back first.']);
        }

        $marks = Mark::with('subject')->where('exam_id', $exam->id)->get();
        $wanted = mb_strtolower(trim($part));
        $report = ['copied' => 0, 'kept' => 0, 'absent' => 0, 'missing_part' => [], 'no_full_marks' => []];

        DB::transaction(function () use ($marks, $sheet, $wanted, $replace, &$report) {
            foreach ($marks->groupBy('subject_id') as $subjectMarks) {
                /** @var Subject|null $subject */
                $subject = $subjectMarks->first()->subject;
                if (! $subject) {
                    continue;
                }
                $component = AssessmentScheme::forSubject($subject)?->components
                    ->first(fn (AssessmentComponent $c) => in_array($wanted, [mb_strtolower($c->short_name), mb_strtolower($c->name)], true));
                if (! $component) {
                    $report['missing_part'][] = $subject->name;
                    continue;
                }
                $full = (float) $subject->full_marks;
                if ($full <= 0) {
                    $report['no_full_marks'][] = $subject->name;
                    continue;
                }
                $existing = SubjectScore::where('result_sheet_id', $sheet->id)->where('subject_id', $subject->id)
                    ->where('assessment_component_id', $component->id)->pluck('student_id')->flip();

                foreach ($subjectMarks as $mark) {
                    if ($mark->is_absent || $mark->marks_obtained === null) {
                        $report['absent']++;
                        continue;
                    }
                    if (! $replace && $existing->has($mark->student_id)) {
                        $report['kept']++;
                        continue;
                    }
                    $score = round(min((float) $mark->marks_obtained, $full) / $full * $component->max_score, 1);
                    SubjectScore::updateOrCreate(
                        ['result_sheet_id' => $sheet->id, 'student_id' => $mark->student_id, 'subject_id' => $subject->id, 'assessment_component_id' => $component->id],
                        ['school_id' => $sheet->school_id, 'score' => $score],
                    );
                    $report['copied']++;
                }
            }
            $this->results->compute($sheet);
        });

        return $report;
    }

    /** The score parts a school uses, by short name, for the copy form */
    public static function partNames(): array
    {
        return AssessmentComponent::orderBy('sort_order')->get(['name', 'short_name'])
            ->unique(fn ($c) => mb_strtolower($c->short_name))
            ->map(fn ($c) => ['value' => $c->short_name, 'label' => $c->short_name === $c->name ? $c->name : "{$c->short_name} ({$c->name})"])
            ->values()->all();
    }
}
