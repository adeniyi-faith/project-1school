<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A photo album on a school's public website (a sports day, a graduation, a new classroom ...) */
class SchoolSiteAlbum extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'slug', 'title', 'description', 'event_date', 'cover_media_id', 'is_published', 'position'];

    protected $casts = ['event_date' => 'date', 'is_published' => 'boolean'];

    public function photos(): HasMany
    {
        return $this->hasMany(SchoolSiteAlbumPhoto::class, 'album_id')->orderBy('position')->orderBy('id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(SchoolSiteMedia::class, 'cover_media_id');
    }
}
