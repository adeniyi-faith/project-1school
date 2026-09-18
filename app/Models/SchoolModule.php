<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolModule extends Model
{
    protected $fillable = ['school_id', 'module_slug', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];
}
