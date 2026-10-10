<?php

namespace App\Console\Commands;

use App\Services\LegacyFeeImporter;
use Illuminate\Console\Command;

class CopyOldFeePayments extends Command
{
    protected $signature = 'fees:copy-old-payments
        {--school= : Only this school id}
        {--dry-run : Show what would be copied without changing anything}
        {--undo : Remove invoices and lines that were copied from old payments}';

    protected $description = 'Copy old fee payments into invoices and the payment ledger (runs once on deploy; safe to run again)';

    public function handle(LegacyFeeImporter $importer): int
    {
        $school = $this->option('school') ? (int) $this->option('school') : null;

        if ($this->option('undo')) {
            $report = $importer->undo($school);
            $this->info("Removed {$report['invoices_removed']} copied invoices and {$report['lines_removed']} copied lines.");
            $this->list('Kept because staff have added to them since (check these by hand):', $report['kept']);

            return self::SUCCESS;
        }

        $report = $importer->copy($school, (bool) $this->option('dry-run'));
        $this->info(($this->option('dry-run') ? 'Would copy' : 'Copied') . " {$report['rows']} old payment rows: "
            . "{$report['invoices_made']} new invoices, {$report['added_to_existing']} added to invoices that already existed.");
        if ($report['multi_row']) {
            $this->line("{$report['multi_row']} invoices were made from more than one old row (instalments).");
        }
        $this->list('Paid more than the fee (the extra shows as a negative balance):', $report['overpaid']);
        $this->list('Not copied:', $report['skipped']);

        return self::SUCCESS;
    }

    private function list(string $title, array $items): void
    {
        if ($items) {
            $this->warn($title);
            foreach ($items as $item) {
                $this->line("  - {$item}");
            }
        }
    }
}
