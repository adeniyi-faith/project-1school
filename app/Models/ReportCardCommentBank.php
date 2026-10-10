<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved report card comment for teachers' or heads' comment boxes, used for students whose
 * average falls in its range. It can hold blanks that are filled in per student:
 * {name} {full_name} {average} {position} {he_she} {him_her} {his_her}.
 */
class ReportCardCommentBank extends Model
{
    use BelongsToSchool;

    protected $table = 'report_card_comment_bank';

    protected $fillable = ['school_id', 'writer_permission', 'min_average', 'max_average', 'comment'];

    protected $casts = ['min_average' => 'float', 'max_average' => 'float'];

    public const BLANKS = ['{name}', '{full_name}', '{average}', '{position}', '{he_she}', '{him_her}', '{his_her}'];

    public function fits(float $average): bool
    {
        return $average >= $this->min_average && $average <= $this->max_average;
    }

    /** The comment with its blanks filled in for one student */
    public static function render(string $text, Student $student, ?float $average, ?int $position, ?int $classSize): string
    {
        $female = $student->gender === 'female';
        $male = $student->gender === 'male';

        $text = strtr($text, [
            '{name}' => $student->first_name,
            '{full_name}' => $student->full_name,
            '{average}' => $average === null ? '' : rtrim(rtrim(number_format($average, 2, '.', ''), '0'), '.').'%',
            '{position}' => $position ? self::ordinal($position).($classSize ? " of {$classSize}" : '') : '',
            '{he_she}' => $female ? 'she' : ($male ? 'he' : 'they'),
            '{him_her}' => $female ? 'her' : ($male ? 'him' : 'them'),
            '{his_her}' => $female ? 'her' : ($male ? 'his' : 'their'),
        ]);

        // "{he_she} works hard" starts a sentence, so it should read "She works hard"
        return preg_replace_callback('/(^|[.!?]\s+)(\p{Ll})/u', fn ($m) => $m[1].mb_strtoupper($m[2]), $text);
    }

    public static function ordinal(int $n): string
    {
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n.$suffix;
    }
}
