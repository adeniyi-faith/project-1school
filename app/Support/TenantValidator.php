<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

/**
 * The normal Laravel validator, with one safety change: a plain "exists:classes,id" rule only accepts rows that
 * belong to the signed-in person's own school (and are not deleted), whenever that table has a school_id column.
 * Without this, someone could type another school's class, student or fee id into a form and it would be accepted.
 * Rules that already name school_id are left exactly as written. Platform owners outside a school are not limited.
 */
class TenantValidator extends Validator
{
    /** @var array<string, bool> */
    private static array $schoolColumn = [];

    /** @var array<string, bool> */
    private static array $softDeletes = [];

    public function validateExists($attribute, $value, $parameters)
    {
        $schoolId = ActingSchool::id() ?? auth()->user()?->school_id;
        [$connection, $table] = $this->parseTable($parameters[0] ?? '');

        if ($schoolId && $table && ! in_array('school_id', $parameters, true) && $this->has($connection, $table, 'school_id', self::$schoolColumn)) {
            $parameters[1] ??= $this->guessColumnForQuery($attribute);
            array_push($parameters, 'school_id', (string) $schoolId);
            if (! in_array('deleted_at', $parameters, true) && $this->has($connection, $table, 'deleted_at', self::$softDeletes)) {
                array_push($parameters, 'deleted_at', 'NULL');
            }
        }

        return parent::validateExists($attribute, $value, $parameters);
    }

    private function has(?string $connection, string $table, string $column, array &$cache): bool
    {
        $key = ($connection ?? '').'|'.$table.'|'.$column;

        return $cache[$key] ??= Schema::connection($connection)->hasColumn($table, $column);
    }
}
