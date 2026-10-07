<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'start_time', 'end_time'];
}
