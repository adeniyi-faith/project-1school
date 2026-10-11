<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * One counter per school and number series ("ADM-2026-", "EMP-2026-" ...). Taking a number locks the
 * counter's row, so two saves at the same moment always get different numbers.
 */
class NumberSequence extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'last'];

    protected $casts = ['last' => 'integer'];

    /**
     * The next free number in a series, e.g. next(5, 'ADM-2026-', 4, $students, 'admission_no') = "ADM-2026-0013".
     *
     * $existing is a query over the records the number must not repeat in, and $column the field that holds it.
     * The first time a series is used, its counter starts after the highest number already in use, and any
     * number already taken (typed by hand, or made before counters existed) is skipped.
     */
    public static function next(int $schoolId, string $prefix, int $pad, Builder $existing, string $column): string
    {
        for ($try = 1; ; $try++) {
            try {
                return DB::transaction(function () use ($schoolId, $prefix, $pad, $existing, $column) {
                    $counter = static::withoutGlobalScopes()->where('school_id', $schoolId)->where('name', $prefix)->lockForUpdate()->first()
                        ?? static::withoutGlobalScopes()->create(['school_id' => $schoolId, 'name' => $prefix, 'last' => static::highest($prefix, (clone $existing), $column)]);

                    $n = $counter->last;
                    do {
                        $n++;
                        $value = $prefix.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
                    } while ((clone $existing)->where($column, $value)->exists());

                    $counter->update(['last' => $n]);

                    return $value;
                });
            } catch (UniqueConstraintViolationException $e) {
                // another save created this series' counter at the same moment: use theirs
                if ($try >= 3) {
                    throw $e;
                }
            }
        }
    }

    /** The highest number already used in a series, from values like "ADM-2026-0042" */
    private static function highest(string $prefix, Builder $existing, string $column): int
    {
        return (int) $existing->where($column, 'like', str_replace(['%', '_'], ['\%', '\_'], $prefix).'%')->pluck($column)
            ->map(fn ($v) => substr((string) $v, strlen($prefix)))
            ->filter(fn ($rest) => ctype_digit($rest))
            ->map(fn ($rest) => (int) $rest)
            ->max();
    }
}
