<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One question in a question bank: objective (choose an option, marked by the computer) or theory (typed answer, marked by a teacher) */
class CbtQuestion extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const MAX_OPTIONS = 6;

    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    public const TYPES = ['objective', 'theory'];

    protected $fillable = ['school_id', 'cbt_bank_id', 'type', 'topic', 'difficulty', 'text', 'image_path', 'options', 'correct_option', 'explanation', 'marking_guide', 'marks', 'created_by'];

    protected $casts = ['options' => 'array', 'correct_option' => 'integer', 'marks' => 'float'];

    protected $attributes = ['type' => 'objective'];

    public function isTheory(): bool
    {
        return $this->type === 'theory';
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }
}
