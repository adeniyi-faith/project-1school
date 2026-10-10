<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Support\SimpleXlsx;
use Illuminate\Support\Collection;

/**
 * Broadsheets: every student's scores for every subject of a class on one page.
 * Term broadsheet: each subject's parts (CA1, CA2, Exam ...), total and grade, then the
 * student's total, average, grade, position and how many of each grade; underneath, each
 * subject's average, highest, lowest and pass rate. Full-year broadsheet: each subject's
 * term totals and year average, then the year average and position.
 */
class BroadsheetService
{
    /** @return array<string, mixed>|null null when the class has no results for the term */
    public function term(ResultSheet $sheet): ?array
    {
        $sheet->loadMissing(['term.academicYear', 'schoolClass']);
        $summaries = TermResultSummary::where('result_sheet_id', $sheet->id)->with('student:id,first_name,last_name,admission_no,gender')->get()
            ->filter(fn ($s) => $s->student)
            ->sortBy([['position', 'asc'], fn ($a, $b) => strcmp($a->student->full_name, $b->student->full_name)])
            ->values();
        if ($summaries->isEmpty()) {
            return null;
        }

        $results = TermResult::where('result_sheet_id', $sheet->id)->get();
        $subjects = Subject::withTrashed()->whereIn('id', $results->pluck('subject_id')->unique())->get(['id', 'name', 'code'])->sortBy('name')->values();
        $scores = SubjectScore::where('result_sheet_id', $sheet->id)->with('component:id,short_name,sort_order')->get();
        $grading = GradingService::forClass($sheet->school_id, $sheet->class_id);
        $grades = $grading->scales()->pluck('grade')->values()->all();
        $passMark = $this->passMark($sheet->school_id);

        // The parts each subject was scored in, in the order the score setup lists them
        $parts = $scores->groupBy('subject_id')->map(fn ($rows) => $rows->pluck('component')->filter()->unique('id')
            ->sortBy('sort_order')->pluck('short_name')->values()->all());
        $partScores = $scores->groupBy(fn ($s) => $s->student_id.'-'.$s->subject_id);
        $byStudent = $results->groupBy('student_id');

        $rows = $summaries->map(function (TermResultSummary $summary) use ($byStudent, $partScores, $grading, $grades) {
            $mine = ($byStudent[$summary->student_id] ?? collect())->keyBy('subject_id');
            $counts = array_fill_keys($grades, 0);
            foreach ($mine as $r) {
                if ($r->grade !== null && isset($counts[$r->grade])) {
                    $counts[$r->grade]++;
                }
            }

            return [
                'student_id' => $summary->student_id,
                'name' => $summary->student->full_name,
                'admission_no' => $summary->student->admission_no,
                'gender' => $summary->student->gender ? strtoupper(substr($summary->student->gender, 0, 1)) : null,
                'scores' => $mine->map(fn (TermResult $r) => [
                    'parts' => ($partScores[$summary->student_id.'-'.$r->subject_id] ?? collect())
                        ->mapWithKeys(fn ($s) => [$s->component?->short_name ?? '?' => $s->score])->all(),
                    'total' => $r->total === null ? null : (float) $r->total,
                    'grade' => $r->grade,
                ])->all(),
                'subjects_count' => $summary->subjects_count,
                'total' => $summary->total_score,
                'average' => $summary->average,
                'grade' => $summary->average === null ? null : $grading->calculate((float) $summary->average, 100)['grade'],
                'position' => $summary->position,
                'grade_counts' => $counts,
            ];
        })->all();

        $footer = [];
        foreach ($subjects as $subject) {
            $totals = $results->where('subject_id', $subject->id)->pluck('total')->filter(fn ($t) => $t !== null)->map(fn ($t) => (float) $t);
            $footer[$subject->id] = $this->stats($totals, $passMark);
        }
        $averages = $summaries->pluck('average')->filter(fn ($a) => $a !== null)->map(fn ($a) => (float) $a);

        return [
            'school' => School::find($sheet->school_id)?->name,
            'class' => $sheet->schoolClass?->name,
            'term' => $sheet->term?->name,
            'session' => $sheet->term?->academicYear?->name,
            'status' => $sheet->status,
            'pass_mark' => $passMark,
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code, 'parts' => $parts[$s->id] ?? []])->all(),
            'grades' => $grades,
            'rows' => $rows,
            'footer' => $footer,
            'overall' => $this->stats($averages, $passMark),
        ];
    }

    /** @return array<string, mixed>|null */
    public function session(AcademicYear $year, int $classId): ?array
    {
        $terms = $year->terms()->get();
        $sheets = ResultSheet::where('class_id', $classId)->whereIn('term_id', $terms->pluck('id'))->with('schoolClass')->get()
            ->sortBy(fn ($s) => $terms->firstWhere('id', $s->term_id)?->sequence)->values();
        $summaries = TermResultSummary::whereIn('result_sheet_id', $sheets->pluck('id'))->with('student:id,first_name,last_name,admission_no,gender')->get()
            ->filter(fn ($s) => $s->student);
        if ($summaries->isEmpty()) {
            return null;
        }
        // Terms with no results yet get no columns
        $sheets = $sheets->filter(fn ($s) => $summaries->contains('result_sheet_id', $s->id))->values();

        $results = TermResult::whereIn('result_sheet_id', $sheets->pluck('id'))->get();
        $subjects = Subject::withTrashed()->whereIn('id', $results->pluck('subject_id')->unique())->get(['id', 'name', 'code'])->sortBy('name')->values();
        $grading = GradingService::forClass($year->school_id, $classId);
        $passMark = $this->passMark($year->school_id);
        $yearAverages = $summaries->groupBy('student_id')->map(fn ($rows) => round($rows->avg('average'), 2));
        $byStudent = $results->groupBy('student_id');

        $rows = $summaries->groupBy('student_id')->map(function (Collection $mine, $studentId) use ($sheets, $byStudent, $subjects, $yearAverages, $grading) {
            $student = $mine->first()->student;
            $results = $byStudent[$studentId] ?? collect();
            $average = $yearAverages[$studentId];

            return [
                'student_id' => (int) $studentId,
                'name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'gender' => $student->gender ? strtoupper(substr($student->gender, 0, 1)) : null,
                'scores' => $subjects->mapWithKeys(function ($subject) use ($sheets, $results, $grading) {
                    $terms = $sheets->map(fn ($sheet) => $results->first(fn ($r) => $r->result_sheet_id === $sheet->id && $r->subject_id === $subject->id)?->total)
                        ->map(fn ($t) => $t === null ? null : (float) $t)->all();
                    $taken = array_filter($terms, fn ($t) => $t !== null);
                    $avg = $taken ? round(array_sum($taken) / count($taken), 2) : null;

                    return [$subject->id => ['terms' => $terms, 'average' => $avg, 'grade' => $avg === null ? null : $grading->calculate($avg, 100)['grade']]];
                })->all(),
                'term_averages' => $sheets->map(fn ($sheet) => $mine->firstWhere('result_sheet_id', $sheet->id)?->average)->all(),
                'average' => $average,
                'grade' => $grading->calculate($average, 100)['grade'],
                'position' => TermResultService::position($average, $yearAverages->values()),
            ];
        })->sortBy([['position', 'asc'], ['name', 'asc']])->values()->all();

        $footer = [];
        foreach ($subjects as $subject) {
            $footer[$subject->id] = $this->stats(collect($rows)->pluck("scores.{$subject->id}.average")->filter(fn ($a) => $a !== null), $passMark);
        }

        return [
            'school' => School::find($year->school_id)?->name,
            'class' => $sheets->first()?->schoolClass?->name,
            'session' => $year->name,
            'pass_mark' => $passMark,
            'term_names' => $sheets->map(fn ($s) => $terms->firstWhere('id', $s->term_id)?->name)->all(),
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code])->all(),
            'rows' => $rows,
            'footer' => $footer,
            'overall' => $this->stats($yearAverages->values(), $passMark),
        ];
    }

    /** One worksheet's rows for a term broadsheet: two heading rows, the students, then the subject figures */
    public function termRows(array $b): array
    {
        $head1 = ['Pos', 'Name', 'Adm. no.', 'Sex'];
        $head2 = ['', '', '', ''];
        $merges = [];
        $col = 5;
        foreach ($b['subjects'] as $s) {
            $span = count($s['parts']) + 2;
            $head1[] = $s['name'];
            array_push($head1, ...array_fill(0, $span - 1, ''));
            array_push($head2, ...[...$s['parts'], 'Total', 'Grade']);
            $merges[] = SimpleXlsx::column($col).'1:'.SimpleXlsx::column($col + $span - 1).'1';
            $col += $span;
        }
        $tail = ['Subjects', 'Total', 'Average', 'Grade', 'Position', ...array_map(fn ($g) => "No. of {$g}", $b['grades'])];
        foreach ($tail as $t) {
            $head1[] = $t;
            $head2[] = '';
            $merges[] = SimpleXlsx::column($col).'1:'.SimpleXlsx::column($col).'2';
            $col++;
        }
        foreach (range(1, 4) as $c) {
            $merges[] = SimpleXlsx::column($c).'1:'.SimpleXlsx::column($c).'2';
        }

        $rows = [$head1, $head2];
        foreach ($b['rows'] as $r) {
            $line = [$r['position'], $r['name'], $r['admission_no'], $r['gender']];
            foreach ($b['subjects'] as $s) {
                $score = $r['scores'][$s['id']] ?? null;
                foreach ($s['parts'] as $p) {
                    $line[] = $score['parts'][$p] ?? null;
                }
                $line[] = $score['total'] ?? null;
                $line[] = $score['grade'] ?? null;
            }
            array_push($line, $r['subjects_count'], $r['total'], $r['average'], $r['grade'], $r['position'], ...array_values($r['grade_counts']));
            $rows[] = $line;
        }

        $rows[] = [];
        foreach (['average' => 'Subject average', 'highest' => 'Highest', 'lowest' => 'Lowest', 'passed' => "Passed ({$b['pass_mark']}% and above)", 'pass_rate' => 'Pass rate (%)'] as $key => $label) {
            $line = ['', $label, '', ''];
            foreach ($b['subjects'] as $s) {
                array_push($line, ...array_fill(0, count($s['parts']), ''));
                $line[] = $b['footer'][$s['id']][$key];
                $line[] = '';
            }
            $rows[] = $line;
        }

        return [$rows, $merges];
    }

    /** One worksheet's rows for a full-year broadsheet */
    public function sessionRows(array $b): array
    {
        $head1 = ['Pos', 'Name', 'Adm. no.', 'Sex'];
        $head2 = ['', '', '', ''];
        $merges = [];
        $col = 5;
        $short = array_map(fn ($n) => $this->shortTerm((string) $n), $b['term_names']);
        foreach ($b['subjects'] as $s) {
            $span = count($short) + 2;
            $head1[] = $s['name'];
            array_push($head1, ...array_fill(0, $span - 1, ''));
            array_push($head2, ...[...$short, 'Avg', 'Grade']);
            $merges[] = SimpleXlsx::column($col).'1:'.SimpleXlsx::column($col + $span - 1).'1';
            $col += $span;
        }
        $tail = [...array_map(fn ($n) => "{$n} average", $b['term_names']), 'Year average', 'Grade', 'Position'];
        foreach ($tail as $t) {
            $head1[] = $t;
            $head2[] = '';
            $merges[] = SimpleXlsx::column($col).'1:'.SimpleXlsx::column($col).'2';
            $col++;
        }
        foreach (range(1, 4) as $c) {
            $merges[] = SimpleXlsx::column($c).'1:'.SimpleXlsx::column($c).'2';
        }

        $rows = [$head1, $head2];
        foreach ($b['rows'] as $r) {
            $line = [$r['position'], $r['name'], $r['admission_no'], $r['gender']];
            foreach ($b['subjects'] as $s) {
                $score = $r['scores'][$s['id']];
                array_push($line, ...$score['terms']);
                $line[] = $score['average'];
                $line[] = $score['grade'];
            }
            array_push($line, ...[...$r['term_averages'], $r['average'], $r['grade'], $r['position']]);
            $rows[] = $line;
        }

        $rows[] = [];
        foreach (['average' => 'Subject average', 'highest' => 'Highest', 'lowest' => 'Lowest', 'passed' => "Passed ({$b['pass_mark']}% and above)", 'pass_rate' => 'Pass rate (%)'] as $key => $label) {
            $line = ['', $label, '', ''];
            foreach ($b['subjects'] as $s) {
                array_push($line, ...array_fill(0, count($short), ''));
                $line[] = $b['footer'][$s['id']][$key];
                $line[] = '';
            }
            $rows[] = $line;
        }

        return [$rows, $merges];
    }

    /** Add a broadsheet as one tab of a workbook */
    public function addSheet(SimpleXlsx $xlsx, string $name, array $b, string $type): void
    {
        [$rows, $merges] = $type === 'session' ? $this->sessionRows($b) : $this->termRows($b);
        $xlsx->sheet($name, $rows, [
            'bold_rows' => [0, 1],
            'merges' => $merges,
            'freeze_rows' => 2,
            'freeze_cols' => 2,
            'widths' => [1 => 5, 2 => 28, 3 => 14, 4 => 5],
        ]);
    }

    /** "First Term" → "1st", anything else keeps its first word */
    public function shortTerm(string $name): string
    {
        return match (true) {
            str_starts_with(strtolower($name), 'first') => '1st',
            str_starts_with(strtolower($name), 'second') => '2nd',
            str_starts_with(strtolower($name), 'third') => '3rd',
            default => mb_substr($name, 0, 8),
        };
    }

    public function passMark(int $schoolId): float
    {
        return (float) (SchoolSetting::get($schoolId, 'pass_mark', 40) ?? 40);
    }

    /** Average, highest, lowest, how many passed and the pass rate of some scores */
    private function stats(Collection $values, float $passMark): array
    {
        $n = $values->count();
        $passed = $values->filter(fn ($v) => $v >= $passMark)->count();

        return [
            'entries' => $n,
            'average' => $n ? round($values->avg(), 2) : null,
            'highest' => $n ? $values->max() : null,
            'lowest' => $n ? $values->min() : null,
            'passed' => $passed,
            'pass_rate' => $n ? round($passed / $n * 100, 1) : null,
        ];
    }
}
