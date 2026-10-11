<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One request a school made to the AI, with the text sizes Google counted and the cost in naira */
class AiUsage extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'user_id', 'feature', 'model', 'input_tokens', 'output_tokens', 'cost_naira', 'ok', 'error'];

    protected $casts = ['input_tokens' => 'integer', 'output_tokens' => 'integer', 'cost_naira' => 'float', 'ok' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
