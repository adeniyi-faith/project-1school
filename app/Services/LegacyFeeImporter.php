<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Copies the old fee_payments rows into invoices and ledger lines, and can undo the copy.
 *
 * Old rows for the same student, fee and period become one invoice:
 *   - one charge for the fee (the largest "amount due" among the rows),
 *   - one discount line (the largest discount; staff retyped it on every instalment),
 *   - a fine line for each row that had a fine,
 *   - a payment line for each row that had money paid, keeping its receipt number, method and date.
 * Every copied line remembers its old row (legacy_fee_payment_id), so running the copy again
 * skips rows already copied, and undo removes exactly what was copied.
 *
 * If an invoice already exists for that student, fee and period (made on the new Invoices screen),
 * the old payments are added to it instead of making a second bill.
 */
class LegacyFeeImporter
{
    public function __construct(private FeeLedgerService $ledger) {}

    /**
     * @return array{rows: int, invoices_made: int, added_to_existing: int, skipped: array<int, string>, overpaid: array<int, string>, multi_row: int}
     */
    public function copy(?int $schoolId = null, bool $dryRun = false): array
    {
        $report = ['rows' => 0, 'invoices_made' => 0, 'added_to_existing' => 0, 'skipped' => [], 'overpaid' => [], 'multi_row' => 0];

        $copied = LedgerEntry::withoutGlobalScopes()->whereNotNull('legacy_fee_payment_id')->pluck('legacy_fee_payment_id')->flip();

        $rows = FeePayment::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('id')
            ->get()
            ->reject(fn (FeePayment $row) => $copied->has($row->id));

        if ($rows->isEmpty()) {
            return $report;
        }

        $structures = FeeStructure::withoutGlobalScopes()->with(['feeCategory' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereIn('id', $rows->pluck('fee_structure_id')->unique())->get()->keyBy('id');
        $studentIds = DB::table('students')->whereIn('id', $rows->pluck('student_id')->unique())->pluck('school_id', 'id');
        $terms = Term::withoutGlobalScopes()->with(['academicYear' => fn ($q) => $q->withoutGlobalScopes()])
            ->whereIn('school_id', $rows->pluck('school_id')->unique())->get();

        $groups = $rows->groupBy(function (FeePayment $row) use ($structures, $terms) {
            $structure = $structures->get($row->fee_structure_id);

            return $row->student_id . '|' . $row->fee_structure_id . '|' . ($structure ? $this->period($row, $structure, $terms)['period'] : '');
        });

        foreach ($groups as $group) {
            /** @var FeePayment $first */
            $first = $group->first();
            $structure = $structures->get($first->fee_structure_id);
            $label = "old receipt {$first->receipt_no}";

            if (! $structure || $structure->school_id !== $first->school_id) {
                $report['skipped'][] = "{$label}: its fee no longer exists";
                continue;
            }
            if ((int) ($studentIds[$first->student_id] ?? 0) !== $first->school_id) {
                $report['skipped'][] = "{$label}: its student no longer exists";
                continue;
            }

            ['period' => $period, 'term' => $term] = $this->period($first, $structure, $terms);

            if ($dryRun) {
                $report['rows'] += $group->count();
                $report['invoices_made']++;
                $report['multi_row'] += $group->count() > 1 ? 1 : 0;
                continue;
            }

            try {
                $outcome = DB::transaction(fn () => $this->copyGroup($group, $structure, $period, $term));
            } catch (Throwable $e) {
                // One bad group must not stop the rest (this also runs during deploys)
                $report['skipped'][] = "{$label}: " . $e->getMessage();
                continue;
            }

            if ($outcome === null) {
                $report['skipped'][] = "{$label}: the matching invoice was cancelled";
                continue;
            }

            [$invoice, $made] = $outcome;
            $report['rows'] += $group->count();
            $report[$made ? 'invoices_made' : 'added_to_existing']++;
            $report['multi_row'] += $group->count() > 1 ? 1 : 0;
            if ($invoice->balance < 0) {
                $report['overpaid'][] = "{$invoice->invoice_no}: paid ₦" . number_format(-$invoice->balance, 2) . ' more than the fee';
            }
        }

        return $report;
    }

    /**
     * Remove what copy() made. Copied lines are deleted; an invoice is deleted only if every line
     * on it was copied. Invoices that staff have added to since are kept and listed.
     *
     * @return array{lines_removed: int, invoices_removed: int, kept: array<int, string>}
     */
    public function undo(?int $schoolId = null): array
    {
        $report = ['lines_removed' => 0, 'invoices_removed' => 0, 'kept' => []];

        $invoiceIds = LedgerEntry::withoutGlobalScopes()->whereNotNull('legacy_fee_payment_id')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->distinct()->pluck('invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            DB::transaction(function () use ($invoiceId, &$report) {
                $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->find($invoiceId);
                if (! $invoice) {
                    return;
                }
                $lines = LedgerEntry::withoutGlobalScopes()->where('invoice_id', $invoiceId)->get();
                $copied = $lines->whereNotNull('legacy_fee_payment_id');

                // A copied line that staff later reversed: removing it would leave the reversal pointing at
                // nothing, so the whole invoice is kept for a person to look at.
                $reversedCopies = $lines->whereIn('reverses_id', $copied->pluck('id'))->isNotEmpty();

                if ($copied->count() === $lines->count()) {
                    // Ledger lines refuse to be deleted through the model; undoing an import is the one
                    // deliberate exception, so it goes straight to the table.
                    DB::table('ledger_entries')->where('invoice_id', $invoiceId)->delete();
                    DB::table('invoices')->where('id', $invoiceId)->delete();
                    $report['lines_removed'] += $copied->count();
                    $report['invoices_removed']++;
                } elseif ($reversedCopies || $copied->contains('type', 'charge')) {
                    $report['kept'][] = "{$invoice->invoice_no}: staff have added lines to it since it was copied";
                } else {
                    // Old payments added to an invoice made on the new screen: take just those lines back out
                    DB::table('ledger_entries')->whereIn('id', $copied->pluck('id'))->delete();
                    $report['lines_removed'] += $copied->count();
                    $this->ledger->refresh($invoice);
                }
            });
        }

        return $report;
    }

    public static function log(string $title, array $report): void
    {
        Log::info($title, $report);
    }

    /** @return array{0: Invoice, 1: bool}|null the invoice and whether it was made now; null if it was cancelled */
    private function copyGroup(Collection $group, FeeStructure $structure, string $period, ?Term $term): ?array
    {
        /** @var FeePayment $first */
        $first = $group->first();

        $invoice = Invoice::withoutGlobalScopes()->where('student_id', $first->student_id)
            ->where('fee_structure_id', $structure->id)->where('period', $period)->lockForUpdate()->first();
        if ($invoice?->status === 'void') {
            return null;
        }

        $made = $invoice === null;
        $firstDate = $group->map(fn (FeePayment $r) => $r->payment_date ?? $r->created_at)->filter()->min();
        $chargeDate = ($structure->due_date ?? $firstDate ?? now())->toDateString();

        if ($made) {
            $amount = round((float) $group->max('amount_due'), 2);
            $invoice = Invoice::withoutGlobalScopes()->create([
                'school_id' => $first->school_id, 'student_id' => $first->student_id, 'fee_structure_id' => $structure->id,
                'term_id' => $term?->id, 'period' => $period, 'amount' => $amount, 'balance' => $amount, 'status' => 'unpaid',
                'due_date' => $structure->due_date,
            ]);
            $invoice->forceFill(['invoice_no' => 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT)])->save();

            $this->line($invoice, $first, 'charge', $amount, $chargeDate, ['note' => ($structure->feeCategory?->name ?? 'Fee') . ' (from old records)']);

            $discountRow = $group->sortByDesc(fn (FeePayment $r) => (float) $r->discount)->first();
            if ((float) $discountRow->discount > 0) {
                $this->line($invoice, $discountRow, 'discount', -round((float) $discountRow->discount, 2), $chargeDate, ['note' => 'Discount from old records']);
            }
        }

        foreach ($group as $row) {
            $date = ($row->payment_date ?? $row->created_at ?? now())->toDateString();
            if ((float) $row->fine > 0) {
                $this->line($invoice, $row, 'fine', round((float) $row->fine, 2), $date, ['note' => 'Fine from old records']);
            }
            if ((float) $row->amount_paid > 0) {
                $this->line($invoice, $row, 'payment', -round((float) $row->amount_paid, 2), $date, [
                    'method' => $row->method, 'reference' => $row->receipt_no, 'note' => $row->note,
                ]);
            }
        }

        $this->ledger->refresh($invoice);

        return [$invoice, $made];
    }

    private function line(Invoice $invoice, FeePayment $row, string $type, float $amount, string $date, array $extra = []): void
    {
        LedgerEntry::withoutGlobalScopes()->create($extra + [
            'school_id' => $invoice->school_id, 'invoice_id' => $invoice->id, 'student_id' => $invoice->student_id,
            'type' => $type, 'amount' => $amount, 'entry_date' => $date, 'legacy_fee_payment_id' => $row->id,
        ]);
    }

    /**
     * The invoice period for an old row. "2026-T1" on a 2026/2027 fee becomes "2026/2027 · First Term",
     * the same label the Invoices screen uses, so billing that term later does not bill these students twice.
     *
     * @return array{period: string, term: Term|null}
     */
    private function period(FeePayment $row, FeeStructure $structure, Collection $terms): array
    {
        $year = str_replace('-', '/', trim((string) $structure->academic_year));

        if (preg_match('/T(\d)$/i', (string) $row->month_year, $m)) {
            $term = $terms->first(fn (Term $t) => $t->school_id === $row->school_id
                && (int) $t->sequence === (int) $m[1]
                && str_replace('-', '/', (string) $t->academicYear?->name) === $year);
            if ($term) {
                return ['period' => trim(($term->academicYear?->name ?? '') . ' · ' . $term->name, ' ·'), 'term' => $term];
            }
        }

        return ['period' => trim((string) $row->month_year) ?: ($year ?: 'Old records'), 'term' => null];
    }
}
