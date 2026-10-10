<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\StudentScholarship;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Every change to what a student owes goes through here, as a new ledger line:
 * invoices (charge), fines, payments, scholarship discounts and reversals.
 * Nothing in the ledger is ever edited, so the history of every naira stays visible.
 */
class FeeLedgerService
{
    /**
     * Bill every active student in the fee's class for one period.
     * Students who already have this invoice are skipped. Returns how many were made.
     */
    public function issueInvoices(FeeStructure $structure, string $period, ?Term $term, ?string $dueDate, User $by): int
    {
        $students = Student::where('school_id', $structure->school_id)
            ->where('class_id', $structure->class_id)
            ->where('status', 'active')
            ->pluck('id');

        $already = Invoice::where('fee_structure_id', $structure->id)->where('period', $period)->pluck('student_id')->flip();
        $made = 0;

        DB::transaction(function () use ($students, $already, $structure, $period, $term, $dueDate, $by, &$made) {
            foreach ($students as $studentId) {
                if ($already->has($studentId)) {
                    continue;
                }

                $invoice = Invoice::create([
                    'school_id' => $structure->school_id,
                    'student_id' => $studentId,
                    'fee_structure_id' => $structure->id,
                    'term_id' => $term?->id,
                    'period' => $period,
                    'amount' => (float) $structure->amount,
                    'balance' => (float) $structure->amount,
                    'status' => 'unpaid',
                    'due_date' => $dueDate ?? $structure->due_date,
                    'issued_by' => $by->id,
                ]);
                // The id is unique, so the number can never clash
                $invoice->forceFill(['invoice_no' => 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT)])->save();

                $this->line($invoice, 'charge', (float) $structure->amount, $by, ['note' => $structure->feeCategory?->name]);

                // Scholarships the student already holds apply straight away
                StudentScholarship::with('scholarship')->where('student_id', $studentId)->whereNull('revoked_at')->get()
                    ->each(fn (StudentScholarship $award) => $this->applyScholarship($award, $invoice, $by, refresh: false));

                $this->refresh($invoice);
                $made++;
            }
        });

        return $made;
    }

    public function recordPayment(Invoice $invoice, float $amount, string $method, string $date, ?string $reference, ?string $note, User $by): LedgerEntry
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount above zero.']);
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $date, $reference, $note, $by) {
            // Lock the invoice so two people paying at once cannot both pass the "not more than owed" check
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($invoice);
            if ($amount > $invoice->balance + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'This is more than the ₦' . number_format($invoice->balance, 2) . ' still owed on this invoice.',
                ]);
            }

            $entry = $this->line($invoice, 'payment', -$amount, $by, [
                'method' => $method,
                'entry_date' => $date,
                // Our own receipt number; a bank or POS reference goes in the note
                'reference' => $reference ?: 'RCP-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'note' => $note,
            ]);
            $this->refresh($invoice);

            return $entry;
        });
    }

    public function addFine(Invoice $invoice, float $amount, ?string $note, User $by): LedgerEntry
    {
        $this->ensureOpen($invoice);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount above zero.']);
        }

        return DB::transaction(function () use ($invoice, $amount, $note, $by) {
            $entry = $this->line($invoice, 'fine', round($amount, 2), $by, ['note' => $note]);
            $this->refresh($invoice);

            return $entry;
        });
    }

    /** Take a scholarship off one invoice, unless it is already applied or does not cover this fee. */
    public function applyScholarship(StudentScholarship $award, Invoice $invoice, User $by, bool $refresh = true): ?LedgerEntry
    {
        $scholarship = $award->scholarship;
        $structure = $invoice->feeStructure;

        if (! $award->isActive() || ! $scholarship?->is_active || ! $structure || ! $scholarship->covers($structure) || ! $invoice->isOpen()) {
            return null;
        }

        $applied = LedgerEntry::where('invoice_id', $invoice->id)->where('student_scholarship_id', $award->id)
            ->where('type', 'discount')->whereDoesntHave('reversedBy')->exists();
        if ($applied) {
            return null;
        }

        $discount = min($scholarship->discountOn($invoice->amount), max(0, $this->balanceOf($invoice)));
        if ($discount <= 0) {
            return null;
        }

        $entry = $this->line($invoice, 'discount', -$discount, $by, [
            'student_scholarship_id' => $award->id,
            'note' => $scholarship->name,
        ]);
        if ($refresh) {
            $this->refresh($invoice);
        }

        return $entry;
    }

    /** Cancel a line by adding its opposite. A line can be reversed once; a reversal cannot be reversed. */
    public function reverse(LedgerEntry $entry, string $reason, User $by): LedgerEntry
    {
        if ($entry->type === 'reversal') {
            throw ValidationException::withMessages(['entry' => 'A reversal cannot itself be reversed. Add a new line instead.']);
        }
        if ($entry->reversedBy()->exists()) {
            throw ValidationException::withMessages(['entry' => 'This line has already been reversed.']);
        }
        $invoice = $entry->invoice;
        if ($invoice->status === 'void') {
            throw ValidationException::withMessages(['entry' => 'This invoice has been cancelled.']);
        }

        return DB::transaction(function () use ($entry, $reason, $by, $invoice) {
            $reversal = $this->line($invoice, 'reversal', -$entry->amount, $by, [
                'reverses_id' => $entry->id,
                'method' => $entry->method,
                'note' => $reason,
            ]);
            $this->refresh($invoice);

            return $reversal;
        });
    }

    /** Cancel an invoice that was made by mistake. Only allowed while nothing has been paid on it. */
    public function void(Invoice $invoice, string $reason, User $by): void
    {
        if ($invoice->status === 'void') {
            return;
        }
        if ($this->netPaid($invoice) > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'Money has been paid on this invoice. Reverse the payments first, then cancel it.',
            ]);
        }

        DB::transaction(function () use ($invoice, $reason, $by) {
            $open = LedgerEntry::where('invoice_id', $invoice->id)->where('type', '!=', 'reversal')->whereDoesntHave('reversedBy')->get();
            foreach ($open as $entry) {
                $this->line($invoice, 'reversal', -$entry->amount, $by, ['reverses_id' => $entry->id, 'note' => 'Invoice cancelled: ' . $reason]);
            }
            $invoice->forceFill(['voided_by' => $by->id, 'voided_at' => now(), 'void_reason' => $reason])->save();
            $this->refresh($invoice);
        });
    }

    /** Bring the invoice's balance and status in line with its ledger. */
    public function refresh(Invoice $invoice): void
    {
        $balance = $this->balanceOf($invoice);
        $status = match (true) {
            $invoice->voided_at !== null => 'void',
            $balance <= 0.004 => 'paid',
            $this->netPaid($invoice) > 0 => 'partial',
            default => 'unpaid',
        };

        $invoice->forceFill(['balance' => $balance, 'status' => $status])->save();
    }

    public function balanceOf(Invoice $invoice): float
    {
        return round((float) LedgerEntry::where('invoice_id', $invoice->id)->sum('amount'), 2);
    }

    /** Money actually paid, after any payment reversals. */
    public function netPaid(Invoice $invoice): float
    {
        $payments = LedgerEntry::where('invoice_id', $invoice->id)->where('type', 'payment')->sum('amount');
        $reversed = LedgerEntry::where('invoice_id', $invoice->id)->where('type', 'reversal')
            ->whereHas('reverses', fn ($q) => $q->where('type', 'payment'))->sum('amount');

        return round(-((float) $payments + (float) $reversed), 2);
    }

    /** Ledger lines that are money received: payments, plus reversals of payments (which take money back out). */
    public static function moneyIn(): Builder
    {
        return LedgerEntry::query()->where(fn ($q) => $q
            ->where('type', 'payment')
            ->orWhere(fn ($r) => $r->where('type', 'reversal')->whereHas('reverses', fn ($p) => $p->where('type', 'payment'))));
    }

    /** Money actually received between two dates (both optional), after any payment reversals. */
    public static function collected(?int $schoolId, ?string $from = null, ?string $to = null): float
    {
        return round(-(float) self::moneyIn()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->sum('amount'), 2);
    }

    /** What is still owed on open invoices. */
    public static function outstanding(?int $schoolId): float
    {
        return round((float) Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('balance'), 2);
    }

    /**
     * A student's fees as parents and students see them: one row per invoice (cancelled ones left out),
     * newest first, with what was charged after discounts and fines, what was paid, and what is left.
     *
     * @return array{total_due: float, total_paid: float, balance: float, rows: Collection}
     */
    public function familyAccount(Student $student): array
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('status', '!=', 'void')
            ->with(['feeStructure.feeCategory:id,name', 'entries'])
            ->orderByDesc('id')
            ->get();

        $rows = $invoices->map(function (Invoice $invoice) {
            $payments = $invoice->entries->where('type', 'payment');
            $paymentReversals = $invoice->entries->where('type', 'reversal')->whereIn('reverses_id', $payments->pluck('id'));
            $paid = round(-($payments->sum('amount') + $paymentReversals->sum('amount')), 2);
            $lastPaid = $payments->max('entry_date');

            return [
                'id' => $invoice->id,
                'month' => trim(($invoice->feeStructure?->feeCategory?->name ?? 'Fee') . ' · ' . $invoice->period),
                'due' => round($invoice->balance + $paid, 2),
                'paid' => $paid,
                'balance' => $invoice->balance,
                'status' => $invoice->status,
                'payment_date' => $lastPaid ? Carbon::parse($lastPaid)->format('d M Y') : null,
            ];
        });

        return [
            'total_due' => round($rows->sum('due'), 2),
            'total_paid' => round($rows->sum('paid'), 2),
            'balance' => round($rows->sum('balance'), 2),
            'rows' => $rows,
        ];
    }

    private function ensureOpen(Invoice $invoice): void
    {
        if ($invoice->status === 'void') {
            throw ValidationException::withMessages(['amount' => 'This invoice has been cancelled.']);
        }
    }

    private function line(Invoice $invoice, string $type, float $amount, User $by, array $extra = []): LedgerEntry
    {
        return LedgerEntry::create($extra + [
            'school_id' => $invoice->school_id,
            'invoice_id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'type' => $type,
            'amount' => round($amount, 2),
            'entry_date' => $extra['entry_date'] ?? now()->toDateString(),
            'recorded_by' => $by->id,
        ]);
    }
}
