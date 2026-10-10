<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What one student owes for one fee in one period. The money itself lives in
 * ledger entries; status and balance here are kept in step with them by FeeLedgerService.
 */
class Invoice extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'invoice_no', 'student_id', 'fee_structure_id', 'term_id', 'period', 'amount',
        'due_date', 'status', 'balance', 'issued_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance' => 'float',
        'due_date' => 'date:Y-m-d',
        'voided_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class)->withTrashed();
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->orderBy('entry_date')->orderBy('id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true);
    }
}
