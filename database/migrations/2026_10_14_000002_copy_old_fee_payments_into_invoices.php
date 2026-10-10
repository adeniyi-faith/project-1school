<?php

use App\Services\LegacyFeeImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Copies every school's old fee payments into invoices and ledger lines.
 * The old fee_payments rows are left untouched.
 *
 * Way back: `php artisan migrate:rollback --step=1` (or `php artisan fees:copy-old-payments --undo`)
 * removes what was copied. An invoice that staff have since added payments or other lines to is
 * kept, so no new money record is ever lost; the command lists those invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        $report = app(LegacyFeeImporter::class)->copy();
        LegacyFeeImporter::log('Copied old fee payments into invoices', $report);
    }

    public function down(): void
    {
        $report = app(LegacyFeeImporter::class)->undo();
        LegacyFeeImporter::log('Removed invoices copied from old fee payments', $report);
    }
};
