<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One attempt by a parent to pay an invoice online. It becomes a ledger payment only after Paystack confirms the money. */
class OnlinePayment extends Model
{
    use BelongsToSchool;

    public const STATUSES = ['pending', 'success', 'failed', 'needs_review'];

    protected $fillable = ['school_id', 'invoice_id', 'student_id', 'paid_by', 'gateway', 'reference', 'amount', 'email', 'status', 'channel',
        'problem', 'ledger_entry_id', 'paid_at', 'checked_at'];

    protected $casts = ['amount' => 'float', 'paid_at' => 'datetime', 'checked_at' => 'datetime'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
