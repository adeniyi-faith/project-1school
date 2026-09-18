<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'grade', 'gpa', 'min_marks', 'max_marks', 'remarks', 'sort_order',
    ];

    protected $casts = [
        'gpa' => 'decimal:2', 'min_marks' => 'decimal:2', 'max_marks' => 'decimal:2',
    ];
}
