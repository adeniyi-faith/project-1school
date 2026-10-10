<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One line in a student's fee account. Lines are never edited or deleted:
 * a mistake is corrected by adding a reversal line that cancels it.
 *
 * Amounts are signed: a charge or fine adds to what is owed (+),
 * a payment or discount takes away from it (−). An invoice's balance is the sum.
 */
class LedgerEntry extends Model
{
    use BelongsToSchool, LogsActivity;

    public const UPDATED_AT = null;

    public const TYPES = ['charge', 'fine', 'payment', 'discount', 'reversal'];

    protected $fillable = [
        'school_id', 'invoice_id', 'student_id', 'type', 'amount', 'method', 'reference',
        'entry_date', 'note', 'student_scholarship_id', 'reverses_id', 'recorded_by', 'legacy_fee_payment_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'entry_date' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger lines cannot be changed. Add a reversal instead.'));
        static::deleting(fn () => throw new LogicException('Ledger lines cannot be deleted. Add a reversal instead.'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_id', 'student_id', 'type', 'amount', 'method', 'reference', 'entry_date', 'note', 'reverses_id'])
            ->useLogName('fees')
            ->setDescriptionForEvent(fn (string $event) => "Ledger line {$event}");
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversedBy(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function studentScholarship(): BelongsTo
    {
        return $this->belongsTo(StudentScholarship::class);
    }
}
