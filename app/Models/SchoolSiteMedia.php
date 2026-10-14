<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A picture in a school's website library, used by pages and posts */
class SchoolSiteMedia extends Model
{
    use BelongsToSchool;

    protected $table = 'school_site_media';

    protected $fillable = ['school_id', 'path', 'name', 'size', 'width', 'height', 'alt', 'uploaded_by'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
