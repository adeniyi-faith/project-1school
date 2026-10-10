<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One person's sitting of a test: the paper they got, their answers and their score */
class CbtAttempt extends Model
{
    use BelongsToSchool;

    /** Seconds allowed after the deadline for the last answer to arrive over a slow connection */
    public const GRACE_SECONDS = 30;

    protected $fillable = ['school_id', 'cbt_test_id', 'student_id', 'started_at', 'deadline_at', 'extra_minutes', 'submitted_at', 'status', 'ended_reason',
        'paper', 'answers', 'flags', 'page_leaves', 'marking', 'section_scores', 'score', 'max_score'];

    protected $casts = [
        'started_at' => 'datetime', 'deadline_at' => 'datetime', 'submitted_at' => 'datetime', 'extra_minutes' => 'integer', 'page_leaves' => 'integer',
        'paper' => 'array', 'answers' => 'array', 'flags' => 'array', 'marking' => 'array', 'section_scores' => 'array', 'score' => 'float', 'max_score' => 'float',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(CbtTest::class, 'cbt_test_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isFinished(): bool
    {
        return $this->status !== 'in_progress';
    }

    /** Time is up, allowing a little grace for answers already on their way */
    public function isOverdue(): bool
    {
        return now()->greaterThan($this->deadline_at->copy()->addSeconds(self::GRACE_SECONDS));
    }

    public function secondsLeft(): int
    {
        return max(0, (int) floor(now()->diffInSeconds($this->deadline_at, false)));
    }
}
