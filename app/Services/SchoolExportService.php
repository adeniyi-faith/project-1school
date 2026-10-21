<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

/**
 * A copy of everything one school owns, as a ZIP of spreadsheet-style (CSV) files, one per table.
 * Read-only: nothing in the app changes. Passwords, keys, tokens and PINs are never written.
 */
class SchoolExportService
{
    /** Tables that hold secrets or throw-away data: not exported at all */
    private const SKIP_TABLES = [
        'school_api_tokens', 'school_webhooks', 'cbt_candidates', 'sessions', 'password_reset_tokens', 'personal_access_tokens',
        'jobs', 'failed_jobs', 'cache', 'cache_locks', 'migrations',
    ];

    /** A column whose name contains one of these is left out of every table */
    private const SECRET_WORDS = ['password', 'remember_token', 'secret', 'token', 'api_key', 'pin'];

    /** Settings rows (school_settings) whose key contains one of these are left out: payment and email keys */
    private const SECRET_SETTING_WORDS = ['key', 'secret', 'token', 'password'];

    /** @return list<string> tables that carry a school_id and are not skipped */
    public function tables(): array
    {
        $tables = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, self::SKIP_TABLES, true) || ! Schema::hasColumn($table, 'school_id')) {
                continue;
            }
            $tables[] = $table;
        }
        sort($tables);

        return $tables;
    }

    /** Builds the ZIP and returns its path (a temporary file the caller sends and deletes) */
    public function build(School $school, User $by, string $reason): string
    {
        $path = tempnam(sys_get_temp_dir(), 'schoolexport');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $temps = [];
        $counts = [];

        foreach ($this->tables() as $table) {
            $columns = array_values(array_filter(Schema::getColumnListing($table), fn ($c) => ! $this->isSecret($c)));
            $query = DB::table($table)->where('school_id', $school->id);
            if ($table === 'school_settings' && in_array('key', $columns, true)) {
                foreach (self::SECRET_SETTING_WORDS as $w) {
                    $query->where('key', 'not like', "%{$w}%");
                }
            }
            $rows = (clone $query)->count();
            if ($rows === 0) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'schoolexportcsv');
            $temps[] = $tmp;
            $out = fopen($tmp, 'w');
            fputcsv($out, $columns);
            foreach ($query->select($columns)->cursor() as $row) {
                fputcsv($out, array_map(fn ($v) => $this->cell($v), array_values((array) $row)));
            }
            fclose($out);

            $zip->addFile($tmp, "{$table}.csv");
            $counts[$table] = $rows;
        }

        $zip->addFromString('README.txt', $this->readme($school, $counts));
        $zip->close();
        foreach ($temps as $t) {
            @unlink($t);
        }

        activity()->causedBy($by)->performedOn($school)
            ->withProperties(['reason' => $reason, 'tables' => count($counts), 'records' => array_sum($counts)])->log('School data exported');

        return $path;
    }

    private function isSecret(string $column): bool
    {
        $c = strtolower($column);
        foreach (self::SECRET_WORDS as $w) {
            if ($w === 'pin' ? (bool) preg_match('/(^|_)pin($|_)/', $c) : str_contains($c, $w)) {
                return true;
            }
        }

        return false;
    }

    /** CSV cells: text as is, and anything that could run as a spreadsheet formula is made harmless */
    private function cell(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        $s = is_scalar($v) ? (string) $v : json_encode($v);

        return $s !== '' && in_array($s[0], ['=', '+', '-', '@'], true) && ! is_numeric($s) ? "'".$s : $s;
    }

    private function readme(School $school, array $counts): string
    {
        $lines = [
            "Copy of the data of {$school->name}",
            'Made on '.now()->toDateTimeString().' (server time).',
            '',
            'One file per table, opened with Excel or any spreadsheet program.',
            'Passwords, keys, tokens and exam PINs are NOT included.',
            'Uploaded files (photos, documents) are NOT included, only the records that point to them.',
            'This is a copy for keeping and for answering data requests. There is no automatic restore from it.',
            '',
            'Records per file:',
        ];
        foreach ($counts as $table => $n) {
            $lines[] = "  {$table}.csv  {$n}";
        }

        return implode("\n", $lines)."\n";
    }
}
