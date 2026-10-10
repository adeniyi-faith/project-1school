<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A timed test for a class, or an entrance exam for applicants, made of sections drawn from question banks */
class CbtTest extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const STATUSES = ['draft', 'published', 'closed'];

    protected $fillable = ['school_id', 'title', 'audience', 'class_id', 'subject_id', 'term_id', 'instructions', 'duration_minutes', 'opens_at', 'closes_at',
        'shuffle_questions', 'shuffle_options', 'show_score', 'access_code', 'max_page_leaves', 'pass_mark', 'status', 'created_by'];

    protected $casts = [
        'opens_at' => 'datetime', 'closes_at' => 'datetime', 'duration_minutes' => 'integer', 'max_page_leaves' => 'integer', 'pass_mark' => 'float',
        'shuffle_questions' => 'boolean', 'shuffle_options' => 'boolean', 'show_score' => 'boolean',
    ];

    protected $hidden = ['access_code'];

    public function sections(): HasMany
    {
        return $this->hasMany(CbtTestSection::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CbtAttempt::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(CbtCandidate::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** Published, opened, and not yet closed */
    public function isOpen(): bool
    {
        return $this->status === 'published' && $this->opens_at->isPast() && (! $this->closes_at || $this->closes_at->isFuture());
    }

    /** Questions each person answers */
    public function questionTotal(): int
    {
        return (int) $this->sections->sum('question_count');
    }
}
