<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One score part, e.g. "First CA" worth 20 marks, or "Examination" worth 60. */
class AssessmentComponent extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'assessment_scheme_id', 'name', 'short_name', 'max_score', 'sort_order'];

    protected $casts = ['max_score' => 'float', 'sort_order' => 'integer'];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(AssessmentScheme::class, 'assessment_scheme_id');
    }
}
