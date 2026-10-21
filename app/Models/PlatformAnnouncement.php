<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notice from the platform team to schools. It has no school_id of its own:
 * who it is for is worked out from `audience`.
 */
class PlatformAnnouncement extends Model
{
    public const KINDS = ['notice' => 'Notice', 'maintenance' => 'Planned maintenance'];
    public const AUDIENCES = ['all' => 'Every school', 'package' => 'Schools on one plan', 'schools' => 'Chosen schools'];

    protected $fillable = [
        'title', 'body', 'kind', 'audience', 'package_id', 'school_ids', 'show_banner', 'send_bell', 'send_email',
        'starts_at', 'ends_at', 'status', 'published_at', 'published_by', 'bell_count', 'email_count', 'email_failed', 'created_by',
    ];

    protected $casts = [
        'school_ids' => 'array', 'show_banner' => 'boolean', 'send_bell' => 'boolean', 'send_email' => 'boolean',
        'starts_at' => 'datetime', 'ends_at' => 'datetime', 'published_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** Ids of the schools this is for */
    public function schoolIds(): array
    {
        return match ($this->audience) {
            'schools' => array_map('intval', $this->school_ids ?? []),
            'package' => SchoolSubscription::where('package_id', $this->package_id)->whereIn('status', ['active', 'trial'])->pluck('school_id')->unique()->values()->all(),
            default   => School::pluck('id')->all(),
        };
    }

    /** Banners on screen right now for one school */
    public function scopeBannerFor(Builder $query, int $schoolId): Builder
    {
        $now = now();

        return $query->where('status', 'published')->where('show_banner', true)
            // A maintenance notice is shown ahead of the work, until the work ends; a notice only inside its own window
            ->where(fn ($q) => $q->where('kind', 'maintenance')->orWhereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->where(function ($q) use ($schoolId) {
                $q->where('audience', 'all')
                    ->orWhere(fn ($w) => $w->where('audience', 'schools')->whereJsonContains('school_ids', $schoolId))
                    ->orWhere(fn ($w) => $w->where('audience', 'package')->whereIn('package_id',
                        SchoolSubscription::where('school_id', $schoolId)->whereIn('status', ['active', 'trial'])->select('package_id')));
            });
    }
}
