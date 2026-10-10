<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A named collection of questions, such as "JSS 1 Mathematics" or "Entrance English" */
class CbtBank extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'subject_id', 'description', 'created_by'];

    public function questions(): HasMany
    {
        return $this->hasMany(CbtQuestion::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
