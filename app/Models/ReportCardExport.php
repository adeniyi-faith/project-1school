<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A bulk report card download being built step by step, then kept for a couple of days. */
class ReportCardExport extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'term_id', 'type', 'layout', 'units', 'next_unit', 'cards', 'status', 'file_path', 'error', 'created_by'];

    protected $casts = ['units' => 'array', 'next_unit' => 'integer', 'cards' => 'integer'];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalUnits(): int
    {
        return count($this->units ?? []);
    }
}
