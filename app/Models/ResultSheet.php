<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One class's results for one term. Results move through
 * draft → submitted → approved → published → locked.
 * Scores can only change while the sheet is a draft, and parents and
 * students only see results once they are published.
 */
class ResultSheet extends Model
{
    use BelongsToSchool;

    public const STATUSES = ['draft', 'submitted', 'approved', 'published', 'locked'];

    /**
     * Each step: [from status, to status, permission needed, stamp to set].
     * "return", "unpublish" and "unlock" send results back a step.
     */
    public const ACTIONS = [
        'submit'    => ['draft', 'submitted', 'marks.entry', 'submitted'],
        'approve'   => ['submitted', 'approved', 'results.publish', 'approved'],
        'return'    => ['submitted|approved', 'draft', 'results.publish', null],
        'publish'   => ['approved', 'published', 'results.publish', 'published'],
        'unpublish' => ['published', 'approved', 'results.publish', null],
        'lock'      => ['published', 'locked', 'results.lock', 'locked'],
        'unlock'    => ['locked', 'published', 'results.lock', null],
    ];

    protected $fillable = ['school_id', 'term_id', 'class_id', 'status', 'version', 'class_average', 'computed_at'];

    protected $casts = [
        'version' => 'integer',
        'class_average' => 'float',
        'computed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(SubjectScore::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TermResult::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(TermResultSummary::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(BehaviourRating::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isVisibleToFamilies(): bool
    {
        return in_array($this->status, ['published', 'locked'], true);
    }

    /** The steps this user may take from the current status. */
    public function availableActions(?User $user): array
    {
        return collect(self::ACTIONS)
            ->filter(fn ($a) => in_array($this->status, explode('|', $a[0]), true) && $user?->can($a[2]))
            ->keys()->values()->all();
    }

    /** Move to the next status. Returns false if the step is not allowed from here. */
    public function transition(string $action, User $user): bool
    {
        if (! in_array($action, $this->availableActions($user), true)) {
            return false;
        }

        [, $to, , $stamp] = self::ACTIONS[$action];
        $from = $this->status;
        $this->status = $to;
        if ($stamp) {
            $this->{"{$stamp}_by"} = $user->id;
            $this->{"{$stamp}_at"} = now();
        }
        $this->save();

        activity('results')->causedBy($user)->performedOn($this)
            ->withProperties(['from' => $from, 'to' => $to, 'term_id' => $this->term_id, 'class_id' => $this->class_id])
            ->log("Results {$action}");

        return true;
    }
}
