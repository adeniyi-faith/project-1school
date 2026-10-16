<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'author_id', 'title', 'body',
        'audience', 'class_id', 'target_role', 'is_pinned', 'published_at',
    ];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo      { return $this->belongsTo(User::class, 'author_id'); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }

    /** Only notices whose publish time has come (a notice set for next week stays hidden until then) */
    public function scopePublished($q)
    {
        return $q->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Notices meant for everyone, for this role, or for one of these classes */
    public function scopeFor($q, string $role, array $classIds = [])
    {
        return $q->where(fn ($w) => $w->where('audience', 'all')
            ->orWhere(fn ($r) => $r->where('audience', 'role')->where('target_role', $role))
            ->orWhere(fn ($r) => $r->where('audience', 'class')->whereIn('class_id', $classIds ?: [0])));
    }
}
