<?php

namespace App\Services;

/**
 * Reads a filled-in score spreadsheet (one subject, one class, one term) into score lines.
 * Nothing is saved here: it returns the lines and every problem found, so the caller can
 * refuse the whole file when there is even one problem.
 */
class ScoreImportService
{
    /**
     * @param  list<list<string>>  $rows        rows of the first sheet
     * @param  iterable<array{id:int,name:string,admission_no:?string}>  $students
     * @param  iterable<object>  $components   score parts (id, short_name, max_score)
     * @return array{0: list<array{student_id:int,component_id:int,score:float}>, 1: list<string>}
     */
    public static function parse(array $rows, iterable $students, iterable $components): array
    {
        $byAdmission = [];
        foreach ($students as $s) {
            if ($s['admission_no'] !== null && $s['admission_no'] !== '') {
                $byAdmission[self::key($s['admission_no'])] = $s;
            }
        }

        // The heading row is the first row that has an "Admission No" cell
        $headerAt = null;
        foreach ($rows as $i => $row) {
            foreach ($row as $cell) {
                if (self::key($cell) === 'admission no') {
                    $headerAt = $i;
                    break 2;
                }
            }
        }
        if ($headerAt === null) {
            return [[], ['The file has no "Admission No" heading. Download the template again and fill that one.']];
        }

        $heading = array_map(fn ($c) => self::key($c), $rows[$headerAt]);
        $admissionCol = array_search('admission no', $heading, true);

        // "CA1 (20)" and "CA1" both mean the part called CA1
        $partCols = [];
        foreach ($components as $part) {
            foreach ($heading as $col => $text) {
                if (preg_replace('/\s*\(.*\)\s*$/', '', $text) === self::key($part->short_name)) {
                    $partCols[$col] = $part;
                    break;
                }
            }
        }
        if (! $partCols) {
            return [[], ['None of the score columns match this subject\'s score parts. Download the template for this subject and fill that one.']];
        }

        $lines = [];
        $problems = [];
        $seen = [];
        foreach ($rows as $i => $row) {
            if ($i <= $headerAt) {
                continue;
            }
            $admission = trim((string) ($row[$admissionCol] ?? ''));
            $scores = [];
            foreach ($partCols as $col => $part) {
                $scores[$col] = trim((string) ($row[$col] ?? ''));
            }
            if ($admission === '' && implode('', $scores) === '') {
                continue;
            }

            $label = 'Row '.($i + 1);
            if ($admission === '') {
                $problems[] = "$label: the admission number is empty.";
                continue;
            }
            $student = $byAdmission[self::key($admission)] ?? null;
            if (! $student) {
                $problems[] = "$label: {$admission} is not a student of this class who takes this subject.";
                continue;
            }
            if (isset($seen[$student['id']])) {
                $problems[] = "$label: {$admission} appears twice (first on row {$seen[$student['id']]}).";
                continue;
            }
            $seen[$student['id']] = $i + 1;

            foreach ($scores as $col => $text) {
                if ($text === '') {
                    continue; // empty cell: leave whatever is stored
                }
                $part = $partCols[$col];
                if (! is_numeric($text)) {
                    $problems[] = "$label: {$part->short_name} for {$admission} is \"{$text}\", which is not a number.";
                } elseif ((float) $text < 0) {
                    $problems[] = "$label: {$part->short_name} for {$admission} cannot be below 0.";
                } elseif ((float) $text > (float) $part->max_score) {
                    $problems[] = "$label: {$part->short_name} for {$admission} is {$text}, but it is out of ".(float) $part->max_score.'.';
                } else {
                    $lines[] = ['student_id' => $student['id'], 'component_id' => $part->id, 'score' => round((float) $text, 2)];
                }
            }
        }

        if (! $problems && ! $lines) {
            $problems[] = 'No scores were found in the file.';
        }

        return [$lines, $problems];
    }

    /** The heading row and one row per student, with scores already stored filled in. */
    public static function template(iterable $students, iterable $components, array $stored): array
    {
        $components = collect($components)->values();
        $rows = [array_merge(['Admission No', 'Student'], $components->map(fn ($c) => $c->short_name.' ('.(float) $c->max_score.')')->all())];
        foreach ($students as $s) {
            $rows[] = array_merge(
                [$s['admission_no'] ?? '', $s['name']],
                $components->map(fn ($c) => isset($stored[$s['id']][$c->id]) ? (float) $stored[$s['id']][$c->id] : '')->all(),
            );
        }

        return $rows;
    }

    private static function key(?string $v): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $v)));
    }
}
