<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One page of a school's public website; `blocks` is the ordered list of content blocks */
class SchoolSitePage extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'slug', 'title', 'seo_title', 'seo_description', 'blocks', 'is_published', 'is_home', 'show_in_menu', 'position', 'parent_id'];

    protected $casts = [
        'blocks'       => 'array',
        'is_published' => 'boolean',
        'is_home'      => 'boolean',
        'show_in_menu' => 'boolean',
    ];
}
