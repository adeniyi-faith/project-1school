<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** An arm of a class, such as JSS 1 A or JSS 1 Emerald. Senior arms can belong to a department (Science, Arts ...). */
class Section extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'class_id', 'name', 'department', 'capacity'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
