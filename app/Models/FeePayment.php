<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'student_id', 'fee_structure_id', 'receipt_no',
        'amount_due', 'amount_paid', 'discount', 'fine',
        'payment_date', 'month_year', 'method', 'status', 'note',
    ];

    protected $casts = [
        'amount_due'   => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'discount'     => 'decimal:2',
        'fine'         => 'decimal:2',
        'payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment) {
            if (empty($payment->receipt_no)) {
                $payment->receipt_no = 'RCP-' . date('Y') . '-' . str_pad(
                    (FeePayment::withoutGlobalScopes()->where('school_id', $payment->school_id)->count() + 1),
                    5, '0', STR_PAD_LEFT
                );
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->amount_due + (float) $this->fine - (float) $this->discount - (float) $this->amount_paid;
    }
}
