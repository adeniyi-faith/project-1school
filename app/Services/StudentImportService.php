<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Support\SimpleXlsx;
use App\Support\SimpleXlsxReader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adding many students at once from a spreadsheet. The school downloads a template, fills one row per
 * child, uploads it and sees every row checked (nothing is saved yet). Rows that pass are then added;
 * rows with a problem are left out and listed so they can be fixed and uploaded again.
 */
class StudentImportService
{
    public const MAX_ROWS = 1000;

    /** key => [heading in the template, example, aliases people may use instead] */
    public const COLUMNS = [
        'first_name'      => ['First name *', 'Adaeze', ['firstname', 'first', 'given name', 'forename']],
        'last_name'       => ['Last name', 'Okafor', ['lastname', 'surname', 'family name']],
        'gender'          => ['Gender *', 'Female', ['sex']],
        'date_of_birth'   => ['Date of birth', '2015-03-21', ['dob', 'birth date', 'birthday', 'date of birth (yyyy-mm-dd)']],
        'class'           => ['Class *', 'JSS 1', ['class name', 'grade', 'level']],
        'arm'             => ['Arm', 'A', ['section', 'class arm', 'division']],
        'admission_no'    => ['Admission number', '', ['admission no', 'admission no.', 'adm no', 'admission', 'reg no', 'registration number']],
        'roll_no'         => ['Roll number', '', ['roll no', 'roll']],
        'admission_date'  => ['Admission date', '2026-09-14', ['date admitted', 'date of admission']],
        'student_email'   => ['Student email', '', ['email', 'student e-mail']],
        'religion'        => ['Religion', 'Christianity', []],
        'blood_group'     => ['Blood group', 'O+', ['blood']],
        'address'         => ['Home address', '12 Allen Avenue, Ikeja', ['address']],
        'previous_school' => ['Previous school', '', ['last school', 'former school']],
        'parent_name'     => ['Parent or guardian name *', 'Mrs Ngozi Okafor', ['guardian name', 'guardian', 'parent', 'parent name', 'parents name']],
        'parent_relation' => ['Relationship', 'Mother', ['relation', 'guardian relation', 'parent relation']],
        'parent_phone'    => ['Parent phone', '08031234567', ['guardian phone', 'parent phone number', 'phone', 'phone number', 'parent mobile']],
        'parent_email'    => ['Parent email', '', ['guardian email', 'parent e-mail']],
    ];

    /** The template as an Excel file: one sheet to fill in, one that lists this school's real class and arm names */
    public function template(int $schoolId): string
    {
        $headings = array_map(fn ($c) => $c[0], self::COLUMNS);
        $example = array_map(fn ($c) => $c[1], self::COLUMNS);

        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('numeric_name')->get(['id', 'name']);
        $arms = Section::where('school_id', $schoolId)->orderBy('name')->get(['class_id', 'name'])->groupBy('class_id');
        $guide = [['Class (type it exactly like this)', 'Arms in that class']];
        foreach ($classes as $c) {
            $guide[] = [$c->name, ($arms[$c->id] ?? collect())->pluck('name')->implode(', ')];
        }
        $guide[] = [''];
        $guide[] = ['How to fill the first sheet'];
        $guide[] = ['Columns marked * must be filled in. The Adaeze Okafor example row is only an example and is ignored.'];
        $guide[] = ['Gender: Male or Female. Dates: 2015-03-21 or 21/03/2015.'];
        $guide[] = ['Admission number: leave empty and the system gives each child the next number.'];
        $guide[] = ['Children of the same parent: use the same parent phone number and they are linked to one parent.'];

        return (new SimpleXlsx)
            ->sheet('Students', [$headings, $example], ['bold_rows' => [0], 'freeze_rows' => 1, 'widths' => array_fill(0, count($headings), 22)])
            ->sheet('Classes and arms', $guide, ['bold_rows' => [0], 'widths' => [0 => 36, 1 => 40]])
            ->bytes();
    }

    /** The template as a plain CSV file for people without Excel */
    public function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map(fn ($c) => $c[0], self::COLUMNS));
        fputcsv($out, array_map(fn ($c) => $c[1], self::COLUMNS));
        rewind($out);

        return (string) stream_get_contents($out);
    }

    /**
     * Rows read from an uploaded .xlsx or .csv file, each as key => text, keyed by the template's columns.
     *
     * @return list<array<string, string>>
     */
    public function read(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        try {
            $lines = $ext === 'xlsx' ? SimpleXlsxReader::rows($file->getRealPath(), self::MAX_ROWS + 5) : $this->csvRows($file->getRealPath());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        // The heading row is the first row that names a first-name column
        $map = null;
        $rows = [];
        foreach ($lines as $line) {
            if ($map === null) {
                $found = $this->headingMap($line);
                if (isset($found['first_name'])) {
                    $map = $found;
                }

                continue;
            }
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = array_fill_keys(array_keys(self::COLUMNS), '');
            foreach ($map as $key => $i) {
                $row[$key] = trim((string) ($line[$i] ?? ''));
            }
            // The template's own example child is never imported
            if ($row['first_name'] === self::COLUMNS['first_name'][1] && $row['last_name'] === self::COLUMNS['last_name'][1] && $row['parent_phone'] === self::COLUMNS['parent_phone'][1]) {
                continue;
            }
            $rows[] = $row;
        }

        if ($map === null) {
            throw ValidationException::withMessages(['file' => 'The headings were not found. Start from the template: the first row must say "First name", "Gender", "Class" and so on.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Add at most '.self::MAX_ROWS.' students at a time. Split the file and upload it in parts.']);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no students in it yet.']);
        }

        return $rows;
    }

    /**
     * Check every row against the school's classes, arms and existing students. Nothing is saved.
     *
     * @param  list<array<string, string>>  $rows
     * @return array{rows: list<array<string, mixed>>, ok: int, bad: int}
     */
    public function check(int $schoolId, array $rows): array
    {
        $classes = SchoolClass::where('school_id', $schoolId)->get(['id', 'name'])->keyBy(fn ($c) => $this->key($c->name));
        $arms = Section::where('school_id', $schoolId)->get(['id', 'class_id', 'name'])->groupBy('class_id')
            ->map(fn ($g) => $g->keyBy(fn ($s) => $this->key($s->name)));
        $taken = Student::withoutGlobalScopes()->withTrashed()->where('school_id', $schoolId)->pluck('admission_no')->filter()
            ->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $known = Student::withoutGlobalScopes()->where('school_id', $schoolId)->get(['first_name', 'last_name', 'date_of_birth'])
            ->mapWithKeys(fn ($s) => [$this->person($s->first_name, $s->last_name, $s->date_of_birth?->toDateString()) => true])->all();

        $seenNo = [];
        $seenPerson = [];
        $out = [];
        $ok = 0;
        foreach ($rows as $n => $r) {
            $errors = [];
            $d = ['line' => $n + 2] + $this->tidy($r, $errors);

            $class = $d['class'] !== '' ? $classes[$this->key($d['class'])] ?? null : null;
            if ($d['class'] === '') {
                $errors[] = 'Class is missing.';
            } elseif (! $class) {
                $errors[] = "There is no class called \"{$d['class']}\".";
            }
            $d['class_id'] = $class?->id;

            $d['section_id'] = null;
            if ($d['arm'] !== '' && $class) {
                $arm = ($arms[$class->id] ?? collect())[$this->key($d['arm'])] ?? null;
                if ($arm) {
                    $d['section_id'] = $arm->id;
                } else {
                    $errors[] = "{$class->name} has no arm called \"{$d['arm']}\".";
                }
            }

            if ($d['admission_no'] !== '') {
                $k = $this->key($d['admission_no']);
                if (isset($taken[$k])) {
                    $errors[] = "Admission number {$d['admission_no']} is already used.";
                } elseif (isset($seenNo[$k])) {
                    $errors[] = "Admission number {$d['admission_no']} appears twice in this file (row {$seenNo[$k]}).";
                }
                $seenNo[$k] ??= $d['line'];
            }

            if ($d['first_name'] !== '') {
                $p = $this->person($d['first_name'], $d['last_name'], $d['date_of_birth']);
                if ($d['date_of_birth'] !== '' && isset($known[$p])) {
                    $errors[] = 'A student with this name and birth date is already in the school.';
                } elseif ($d['date_of_birth'] !== '' && isset($seenPerson[$p])) {
                    $errors[] = "The same child is on row {$seenPerson[$p]} of this file.";
                }
                $seenPerson[$p] ??= $d['line'];
            }

            $d['errors'] = $errors;
            $ok += $errors === [] ? 1 : 0;
            $out[] = $d;
        }

        return ['rows' => $out, 'ok' => $ok, 'bad' => count($out) - $ok];
    }

    /**
     * Add the rows that passed the check. Parents are matched by phone number, so brothers and sisters
     * (and children who already have a parent in the school) share one parent record.
     *
     * @param  list<array<string, mixed>>  $checked  rows from check()
     */
    public function import(int $schoolId, array $checked): int
    {
        $count = 0;
        DB::transaction(function () use ($schoolId, $checked, &$count) {
            $guardians = [];
            foreach ($checked as $d) {
                if ($d['errors'] !== []) {
                    continue;
                }
                $phoneKey = preg_replace('/\D+/', '', $d['parent_phone']);
                $guardian = $phoneKey !== '' ? ($guardians[$phoneKey] ?? null) : null;
                if (! $guardian && $phoneKey !== '') {
                    $guardian = Guardian::where('school_id', $schoolId)->where('phone', $d['parent_phone'])->first();
                }
                if (! $guardian) {
                    $guardian = Guardian::create([
                        'school_id' => $schoolId, 'name' => $d['parent_name'], 'relation' => $d['parent_relation'] ?: 'Parent',
                        'phone' => $d['parent_phone'] ?: null, 'email' => $d['parent_email'] ?: null, 'address' => $d['address'] ?: null,
                    ]);
                }
                if ($phoneKey !== '') {
                    $guardians[$phoneKey] = $guardian;
                }

                Student::create(array_filter([
                    'school_id' => $schoolId,
                    'class_id' => $d['class_id'],
                    'section_id' => $d['section_id'],
                    'guardian_id' => $guardian->id,
                    'first_name' => $d['first_name'],
                    'last_name' => $d['last_name'] ?: null,
                    'gender' => $d['gender'],
                    'date_of_birth' => $d['date_of_birth'] ?: null,
                    'admission_no' => $d['admission_no'] ?: null,
                    'roll_no' => $d['roll_no'] ?: null,
                    'admission_date' => $d['admission_date'] ?: now()->toDateString(),
                    'email' => $d['student_email'] ?: null,
                    'religion' => $d['religion'] ?: null,
                    'blood_group' => $d['blood_group'] ?: null,
                    'address' => $d['address'] ?: null,
                    'previous_school' => $d['previous_school'] ?: null,
                    'status' => 'active',
                    'category' => 'general',
                ], fn ($v) => $v !== null));
                $count++;
            }
        });

        return $count;
    }

    // ── Tidying one row ───────────────────────────────────────────

    /** @param list<string> $errors */
    private function tidy(array $r, array &$errors): array
    {
        $d = array_map(fn ($v) => trim(preg_replace('/\s+/u', ' ', (string) $v)), $r);

        if ($d['first_name'] === '') {
            $errors[] = 'First name is missing.';
        }
        if ($d['parent_name'] === '') {
            $errors[] = 'Parent or guardian name is missing.';
        }

        $g = mb_strtolower($d['gender']);
        $d['gender'] = match (true) {
            in_array($g, ['m', 'male', 'boy', 'man'], true) => 'male',
            in_array($g, ['f', 'female', 'girl', 'woman'], true) => 'female',
            in_array($g, ['o', 'other'], true) => 'other',
            default => '',
        };
        if ($d['gender'] === '') {
            $errors[] = 'Gender must be Male or Female.';
        }

        foreach (['date_of_birth' => 'Date of birth', 'admission_date' => 'Admission date'] as $k => $label) {
            if ($d[$k] === '') {
                continue;
            }
            $date = $this->date($d[$k]);
            if (! $date) {
                $errors[] = "{$label} \"{$d[$k]}\" is not a date. Use 2015-03-21 or 21/03/2015.";
            } elseif ($k === 'date_of_birth' && $date->isFuture()) {
                $errors[] = 'Date of birth is in the future.';
            } else {
                $d[$k] = $date->toDateString();
            }
        }

        foreach (['student_email' => 'Student email', 'parent_email' => 'Parent email'] as $k => $label) {
            if ($d[$k] !== '' && ! filter_var($d[$k], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$label} \"{$d[$k]}\" is not a valid email.";
            }
        }

        // Excel drops the leading zero of phone numbers typed as numbers: put it back for Nigerian mobiles
        $phone = preg_replace('/[^\d+]/', '', $d['parent_phone']);
        if (preg_match('/^[789]\d{9}$/', $phone)) {
            $phone = '0'.$phone;
        }
        $d['parent_phone'] = $phone;
        if ($phone !== '' && strlen(preg_replace('/\D/', '', $phone)) < 7) {
            $errors[] = 'Parent phone number looks too short.';
        }

        foreach (['first_name' => 100, 'last_name' => 100, 'parent_name' => 150, 'admission_no' => 50, 'roll_no' => 50, 'address' => 500, 'previous_school' => 200, 'religion' => 50, 'blood_group' => 5, 'parent_relation' => 50] as $k => $max) {
            $d[$k] = mb_substr($d[$k], 0, $max);
        }

        return $d;
    }

    /** A date typed as 2015-03-21, 21/03/2015, 21-03-2015, or an Excel day number such as 42085 */
    private function date(string $v): ?Carbon
    {
        try {
            if (preg_match('/^\d{4,6}(\.\d+)?$/', $v) && (float) $v > 3000 && (float) $v < 80000) {
                return Carbon::create(1899, 12, 30)->addDays((int) $v);
            }
            if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $v, $m)) {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
            }
            if (preg_match('#^\d{4}-\d{1,2}-\d{1,2}#', $v)) {
                return Carbon::parse($v)->startOfDay();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    // ── Reading files ─────────────────────────────────────────────

    /** @return list<list<string>> */
    private function csvRows(string $path): array
    {
        $text = (string) file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $first = strtok($text, "\n") ?: '';
        $delimiter = collect([',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")])->sortDesc()->keys()->first();

        $rows = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $text);
        rewind($handle);
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($v) => (string) $v, $line);
            if (count($rows) > self::MAX_ROWS + 5) {
                break;
            }
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<string, int> column key => position in the file */
    private function headingMap(array $line): array
    {
        $lookup = [];
        foreach (self::COLUMNS as $key => [$label, , $aliases]) {
            foreach (array_merge([$label], $aliases) as $name) {
                $lookup[$this->heading($name)] = $key;
            }
        }
        $map = [];
        foreach ($line as $i => $cell) {
            $key = $lookup[$this->heading((string) $cell)] ?? null;
            if ($key && ! isset($map[$key])) {
                $map[$key] = $i;
            }
        }

        return $map;
    }

    private function heading(string $v): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($v)));
    }

    private function key(string $v): string
    {
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($v));
    }

    private function person(string $first, ?string $last, ?string $dob): string
    {
        return $this->key($first).'|'.$this->key((string) $last).'|'.$dob;
    }
}
