<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceInstalment;
use App\Models\PaymentPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Paying an invoice in dated parts. The parts are a timetable over the invoice, not money: each part is a
 * share (percent) of what the invoice comes to after discounts and fines, and payments fill the parts in
 * order. Both are worked out again whenever the ledger changes (FeeLedgerService::refresh calls spread()).
 */
class InstalmentService
{
    public const MAX_PARTS = 6;

    public function __construct(private FeeLedgerService $ledger) {}

    /**
     * Check a list of parts ([label, percent, due_date] each) and return it cleaned up.
     *
     * @param  bool  $withDates  false for a saved plan, which has no dates of its own
     */
    public static function cleanParts(array $parts, bool $withDates = true): array
    {
        $parts = array_values($parts);
        $errors = [];
        if (count($parts) < 2 || count($parts) > self::MAX_PARTS) {
            $errors['parts'] = 'A plan has between 2 and '.self::MAX_PARTS.' parts.';
        }
        $clean = [];
        $total = 0.0;
        $lastDate = null;
        foreach ($parts as $i => $part) {
            $n = $i + 1;
            $label = trim((string) ($part['label'] ?? '')) ?: "Part {$n}";
            $percent = round((float) ($part['percent'] ?? 0), 2);
            if ($percent <= 0 || $percent >= 100) {
                $errors["parts.{$i}.percent"] = "Part {$n}: give a share between 0 and 100%.";
            }
            $total += $percent;
            $row = ['label' => mb_substr($label, 0, 60), 'percent' => $percent];
            if ($withDates) {
                try {
                    $date = Carbon::parse((string) ($part['due_date'] ?? ''))->startOfDay();
                } catch (\Throwable) {
                    $date = null;
                }
                if (empty($part['due_date']) || ! $date) {
                    $errors["parts.{$i}.due_date"] = "Part {$n}: choose the date it is due.";
                } elseif ($lastDate && $date->lt($lastDate)) {
                    $errors["parts.{$i}.due_date"] = "Part {$n} cannot be due before part {$i}.";
                } else {
                    $lastDate = $date;
                    $row['due_date'] = $date->toDateString();
                }
            }
            $clean[] = $row;
        }
        if (! isset($errors['parts']) && abs($total - 100) > 0.001) {
            $errors['parts'] = 'The parts must add up to 100%. They add up to '.rtrim(rtrim(number_format($total, 2), '0'), '.').'% now.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $clean;
    }

    /** Give one invoice these dated parts, replacing any it had. Paid and cancelled invoices are left alone. */
    public function apply(Invoice $invoice, array $parts, ?PaymentPlan $plan = null): bool
    {
        if (! $invoice->isOpen()) {
            return false;
        }

        DB::transaction(function () use ($invoice, $parts, $plan) {
            InvoiceInstalment::where('invoice_id', $invoice->id)->delete();
            foreach ($parts as $i => $part) {
                InvoiceInstalment::create([
                    'school_id' => $invoice->school_id, 'invoice_id' => $invoice->id, 'position' => $i + 1,
                    'label' => $part['label'], 'percent' => $part['percent'], 'due_date' => $part['due_date'],
                ]);
            }
            // The invoice is due in full on the last part's date
            $invoice->forceFill(['payment_plan_id' => $plan?->id, 'due_date' => end($parts)['due_date']])->save();
            $this->ledger->refresh($invoice);
        });

        return true;
    }

    /**
     * Put the parts on many invoices at once. Invoices that already have parts keep them unless $replace.
     * Returns how many invoices got the parts.
     */
    public function applyToMany(Collection $invoices, array $parts, ?PaymentPlan $plan, bool $replace): int
    {
        $done = 0;
        $invoices->loadCount('instalments');
        foreach ($invoices as $invoice) {
            if (! $replace && $invoice->instalments_count > 0) {
                continue;
            }
            $done += $this->apply($invoice, $parts, $plan) ? 1 : 0;
        }

        return $done;
    }

    /** Go back to one payment for the whole invoice */
    public function remove(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            InvoiceInstalment::where('invoice_id', $invoice->id)->delete();
            $invoice->forceFill(['payment_plan_id' => null])->save();
        });
    }

    /**
     * Work out each part's amount and what it has received. The invoice comes to what is still owed plus
     * what was paid; the last part takes any kobo left over by rounding, and payments fill part 1 first.
     */
    public static function spread(Invoice $invoice, float $balance, float $paid): void
    {
        $parts = InvoiceInstalment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->orderBy('position')->get();
        if ($parts->isEmpty()) {
            return;
        }
        $total = max(0, round($balance + $paid, 2));
        $left = max(0, $paid);
        $shared = 0.0;
        foreach ($parts as $i => $part) {
            $amount = $i === $parts->count() - 1 ? round($total - $shared, 2) : round($total * $part->percent / 100, 2);
            $shared += $amount;
            $got = round(min($amount, $left), 2);
            $left = round($left - $got, 2);
            if (abs($part->amount - $amount) > 0.001 || abs($part->paid - $got) > 0.001) {
                $part->forceFill(['amount' => $amount, 'paid' => $got])->save();
            }
        }
    }

    /** How an invoice's parts stand, for screens. Needs the instalments relation (loaded if missing). */
    public static function summary(Invoice $invoice): ?array
    {
        $parts = $invoice->relationLoaded('instalments') ? $invoice->instalments : $invoice->instalments()->get();
        if ($parts->isEmpty()) {
            return null;
        }
        $rows = $parts->map(fn (InvoiceInstalment $p) => [
            'label' => $p->label, 'percent' => $p->percent, 'due_date' => $p->due_date->toDateString(),
            'amount' => $p->amount, 'paid' => $p->paid, 'left' => $p->left(), 'state' => $invoice->status === 'void' ? 'cancelled' : $p->state(),
        ])->values();
        $next = $rows->first(fn ($r) => $r['left'] > 0);

        return [
            'parts' => $rows,
            // Everything that should have been paid by today and has not been
            'due_now' => round($rows->whereIn('state', ['overdue', 'due'])->sum('left'), 2),
            'overdue' => round($rows->where('state', 'overdue')->sum('left'), 2),
            'next' => $next ? ['label' => $next['label'], 'due_date' => $next['due_date'], 'left' => $next['left']] : null,
        ];
    }
}
