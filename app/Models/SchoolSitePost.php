<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A news story or event on a school's public website */
class SchoolSitePost extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const CATEGORIES = ['news' => 'News', 'events' => 'Events', 'achievements' => 'Achievements', 'admissions' => 'Admissions'];

    protected $fillable = [
        'school_id', 'slug', 'title', 'category', 'excerpt', 'body', 'cover_image', 'status', 'published_at',
        'event_date', 'event_place', 'is_featured', 'seo_title', 'seo_description', 'author_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'event_date'   => 'date',
        'is_featured'  => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not dated in the future */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'News';
    }
}
