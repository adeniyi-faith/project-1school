<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Part of a test: how many questions to draw from which bank (and topic or difficulty) */
class CbtTestSection extends Model
{
    public $timestamps = false;

    protected $fillable = ['cbt_test_id', 'position', 'title', 'cbt_bank_id', 'topic', 'topics', 'difficulty', 'question_type', 'question_count'];

    protected $casts = ['question_count' => 'integer', 'position' => 'integer', 'topics' => 'array'];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CbtBank::class, 'cbt_bank_id');
    }

    /** The questions this section draws from */
    public function pool()
    {
        return CbtQuestion::where('cbt_bank_id', $this->cbt_bank_id)
            ->when($this->topics, fn ($q, $list) => $q->whereIn('topic', $list), fn ($q) => $q->when($this->topic, fn ($q) => $q->where('topic', $this->topic)))
            ->when($this->difficulty, fn ($q) => $q->where('difficulty', $this->difficulty))
            ->when($this->question_type, fn ($q) => $q->where('type', $this->question_type));
    }
}
