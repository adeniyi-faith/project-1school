<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One objective question in a question bank */
class CbtQuestion extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const MAX_OPTIONS = 6;

    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    protected $fillable = ['school_id', 'cbt_bank_id', 'topic', 'difficulty', 'text', 'image_path', 'options', 'correct_option', 'explanation', 'marks', 'created_by'];

    protected $casts = ['options' => 'array', 'correct_option' => 'integer', 'marks' => 'float'];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }
}
