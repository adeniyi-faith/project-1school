<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A school's public website settings: on/off, theme, colour, tagline */
class SchoolSite extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'enabled', 'theme', 'color', 'tagline', 'meta_description', 'footer_text'];

    protected $casts = ['enabled' => 'boolean'];
}
