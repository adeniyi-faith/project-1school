<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One dated part of an invoice. Amount and paid are kept in step with the ledger by InstalmentService. */
class InvoiceInstalment extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'invoice_id', 'position', 'label', 'percent', 'due_date', 'amount', 'paid'];

    protected $casts = [
        'percent' => 'float',
        'amount' => 'float',
        'paid' => 'float',
        'due_date' => 'date:Y-m-d',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function left(): float
    {
        return round(max(0, $this->amount - $this->paid), 2);
    }

    /** paid, overdue (past its date and not fully paid), due (today), or upcoming */
    public function state(): string
    {
        if ($this->left() <= 0.004) {
            return 'paid';
        }
        $today = now()->startOfDay();

        return match (true) {
            $this->due_date->lt($today) => 'overdue',
            $this->due_date->equalTo($today) => 'due',
            default => 'upcoming',
        };
    }
}
