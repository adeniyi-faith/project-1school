<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPromotion;
use App\Models\TermResultSummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * End-of-year moves: promote a class to the next one, keep some students back (with a
 * written reason), or graduate the top class. Each student is moved at most once per
 * school year, so running a class twice, or running JSS 1 before JSS 2, never moves anyone twice.
 */
class PromotionService
{
    /** Result sheets that count towards the year average: checked by the school, not drafts */
    public const COUNTED_SHEETS = ['approved', 'published', 'locked'];

    /** The class with the next number up, or null for the top class (whose students graduate). */
    public function nextClass(SchoolClass $class): ?SchoolClass
    {
        if ($class->numeric_name === null) {
            return null;
        }

        return SchoolClass::where('school_id', $class->school_id)
            ->where('numeric_name', '>', $class->numeric_name)
            ->orderBy('numeric_name')->orderBy('id')
            ->first();
    }

    /** Active students in the class who have not been moved yet this school year. */
    public function waiting(SchoolClass $class, AcademicYear $year): Builder
    {
        return Student::where('school_id', $class->school_id)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->whereDoesntHave('promotions', fn ($q) => $q->whereHas('batch', fn ($b) => $b
                ->where('academic_year_id', $year->id)->whereNull('undone_at')));
    }

    /**
     * Each waiting student with their average across the year's checked term results,
     * and a suggestion: repeat when the average is below the pass mark, graduate in the top class.
     */
    public function candidates(SchoolClass $class, AcademicYear $year, float $passMark): Collection
    {
        $students = $this->waiting($class, $year)->with('section:id,name')
            ->orderBy('first_name')->orderBy('last_name')->get();
        $averages = $this->yearAverages($class, $year, $students->pluck('id')->all());
        $isTop = $this->nextClass($class) === null;

        return $students->map(function (Student $s) use ($averages, $passMark, $isTop) {
            $avg = $averages[$s->id] ?? null;
            $below = $avg !== null && $avg['average'] < $passMark;

            return [
                'id' => $s->id,
                'name' => $s->full_name,
                'admission_no' => $s->admission_no,
                'section_id' => $s->section_id,
                'section' => $s->section?->name,
                'year_average' => $avg['average'] ?? null,
                'terms_counted' => $avg['terms'] ?? 0,
                'suggested' => $below ? 'repeated' : ($isTop ? 'graduated' : 'promoted'),
            ];
        })->values();
    }

    /** student_id => ['average' => mean of term averages, 'terms' => how many terms had results] */
    public function yearAverages(SchoolClass $class, AcademicYear $year, array $studentIds): array
    {
        if (! $studentIds) {
            return [];
        }

        $sheetIds = ResultSheet::where('class_id', $class->id)
            ->whereIn('term_id', $year->terms()->pluck('id'))
            ->whereIn('status', self::COUNTED_SHEETS)
            ->pluck('id');

        return TermResultSummary::whereIn('result_sheet_id', $sheetIds)
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('average')
            ->get(['student_id', 'average'])
            ->groupBy('student_id')
            ->map(fn ($rows) => ['average' => round($rows->avg('average'), 2), 'terms' => $rows->count()])
            ->all();
    }

    /**
     * Move the chosen students. $decisions: [{student_id, outcome, section_id?, reason?}].
     * Students left out of $decisions stay where they are and can be moved later.
     */
    public function run(SchoolClass $from, AcademicYear $year, ?SchoolClass $to, ?float $passMark, array $decisions, User $by): PromotionBatch
    {
        $decisions = collect($decisions)->keyBy('student_id');
        $this->check($from, $to, $decisions);

        return DB::transaction(function () use ($from, $year, $to, $passMark, $decisions, $by) {
            // Lock the rows, and only take students still waiting, so a double click cannot move anyone twice
            $students = $this->waiting($from, $year)->whereIn('id', $decisions->keys())->lockForUpdate()->get()->keyBy('id');
            $gone = $decisions->keys()->diff($students->keys());
            if ($gone->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'decisions' => 'Some of these students have already been moved this year, or are no longer in this class. Reload the page and try again.',
                ]);
            }

            $averages = $this->yearAverages($from, $year, $students->keys()->all());
            $counts = $decisions->countBy('outcome');

            $batch = PromotionBatch::create([
                'school_id' => $from->school_id,
                'academic_year_id' => $year->id,
                'from_class_id' => $from->id,
                'to_class_id' => $to?->id,
                'pass_mark' => $passMark,
                'promoted_count' => $counts['promoted'] ?? 0,
                'repeated_count' => $counts['repeated'] ?? 0,
                'graduated_count' => $counts['graduated'] ?? 0,
                'performed_by' => $by->id,
            ]);

            foreach ($decisions as $id => $d) {
                $student = $students[$id];
                [$toClass, $toSection, $status] = match ($d['outcome']) {
                    'promoted' => [$to->id, $d['section_id'] ?? null, 'active'],
                    'repeated' => [$from->id, $student->section_id, 'active'],
                    'graduated' => [$from->id, $student->section_id, 'alumni'],
                };

                StudentPromotion::create([
                    'school_id' => $from->school_id,
                    'promotion_batch_id' => $batch->id,
                    'student_id' => $student->id,
                    'outcome' => $d['outcome'],
                    'from_class_id' => $student->class_id,
                    'from_section_id' => $student->section_id,
                    'from_status' => $student->status,
                    'to_class_id' => $d['outcome'] === 'graduated' ? null : $toClass,
                    'to_section_id' => $d['outcome'] === 'graduated' ? null : $toSection,
                    'year_average' => $averages[$student->id]['average'] ?? null,
                    'reason' => isset($d['reason']) ? trim($d['reason']) ?: null : null,
                ]);

                $student->update(['class_id' => $toClass, 'section_id' => $toSection, 'status' => $status]);
            }

            activity('students')->causedBy($by)->performedOn($batch)
                ->withProperties([
                    'class' => $from->name, 'to' => $to?->name, 'year' => $year->name,
                    'promoted' => $batch->promoted_count, 'repeated' => $batch->repeated_count, 'graduated' => $batch->graduated_count,
                ])
                ->log('Moved students up for the year');

            return $batch;
        });
    }

    /**
     * Put every student in the batch back where they were. Refused if any of them has been
     * moved, edited into another class, or had their status changed since, so nothing is overwritten.
     */
    public function undo(PromotionBatch $batch, User $by): void
    {
        if ($batch->undone_at) {
            throw ValidationException::withMessages(['batch' => 'This move has already been undone.']);
        }

        DB::transaction(function () use ($batch, $by) {
            $lines = $batch->lines()->get();
            $students = Student::whereIn('id', $lines->pluck('student_id'))->lockForUpdate()->get()->keyBy('id');

            $changed = $lines->filter(function (StudentPromotion $line) use ($students) {
                $s = $students[$line->student_id] ?? null;
                if (! $s) {
                    return true;
                }
                $expected = $line->outcome === 'graduated'
                    ? [$line->from_class_id, $line->from_section_id, 'alumni']
                    : [$line->to_class_id, $line->to_section_id, 'active'];

                return [(int) $s->class_id, $s->section_id ? (int) $s->section_id : null, $s->status]
                    !== [(int) $expected[0], $expected[1] ? (int) $expected[1] : null, $expected[2]];
            });

            if ($changed->isNotEmpty()) {
                $names = $changed->map(fn ($l) => $students[$l->student_id]?->full_name ?? 'a removed student')->take(5)->implode(', ');
                throw ValidationException::withMessages([
                    'batch' => "Can't undo: {$changed->count()} student(s) have been moved or changed since ({$names}). Change them back by hand first, or edit the others one by one.",
                ]);
            }

            foreach ($lines as $line) {
                $students[$line->student_id]->update([
                    'class_id' => $line->from_class_id,
                    'section_id' => $line->from_section_id,
                    'status' => $line->from_status,
                ]);
            }

            $batch->update(['undone_at' => now(), 'undone_by' => $by->id]);

            activity('students')->causedBy($by)->performedOn($batch)
                ->withProperties(['students' => $lines->count()])
                ->log('Undid a year-end move');
        });
    }

    /** The checks that don't need the database rows locked */
    private function check(SchoolClass $from, ?SchoolClass $to, Collection $decisions): void
    {
        if ($decisions->isEmpty()) {
            throw ValidationException::withMessages(['decisions' => 'Choose at least one student.']);
        }
        if ($to && $to->id === $from->id) {
            throw ValidationException::withMessages(['to_class_id' => 'Choose a different class to move up to.']);
        }

        $errors = [];
        foreach ($decisions->values() as $i => $d) {
            $outcome = $d['outcome'] ?? null;
            if (! in_array($outcome, StudentPromotion::OUTCOMES, true)) {
                $errors["decisions.$i.outcome"] = 'Choose move up, repeat or graduate.';
            } elseif ($outcome === 'promoted' && ! $to) {
                $errors['to_class_id'] = 'Choose the class to move up to.';
            } elseif ($outcome === 'repeated' && mb_strlen(trim($d['reason'] ?? '')) < 3) {
                $errors["decisions.$i.reason"] = 'Say why this student is repeating the class.';
            }
        }

        // A chosen section must belong to the class the students are moving to
        $sectionIds = $decisions->where('outcome', 'promoted')->pluck('section_id')->filter()->unique();
        if ($sectionIds->isNotEmpty() && $to) {
            $valid = Section::where('class_id', $to->id)->whereIn('id', $sectionIds)->count();
            if ($valid !== $sectionIds->count()) {
                $errors['decisions'] = 'A chosen section is not part of '.$to->name.'.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
