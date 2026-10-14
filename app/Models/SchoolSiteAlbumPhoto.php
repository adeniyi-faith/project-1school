<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One picture inside an album, with its own caption and place in the order */
class SchoolSiteAlbumPhoto extends Model
{
    use BelongsToSchool;

    protected $table = 'school_site_album_media';

    protected $fillable = ['school_id', 'album_id', 'media_id', 'caption', 'position'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(SchoolSiteMedia::class, 'media_id');
    }
}
