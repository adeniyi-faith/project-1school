<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A 1 (poor) to 5 (excellent) rating of one behaviour or skill for one student in one term. */
class BehaviourRating extends Model
{
    use BelongsToSchool;

    public const LABELS = [5 => 'Excellent', 4 => 'Very good', 3 => 'Good', 2 => 'Fair', 1 => 'Poor'];

    protected $fillable = ['school_id', 'result_sheet_id', 'student_id', 'behaviour_trait_id', 'rating'];

    protected $casts = ['rating' => 'integer'];

    public function behaviourTrait(): BelongsTo
    {
        return $this->belongsTo(BehaviourTrait::class);
    }
}
