<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger lines copied from the old fee_payments table remember which row they came from,
 * so the copy can be run again safely (rows already copied are skipped) and undone.
 * Not a foreign key: the old rows are kept as they are and may be soft-deleted later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_fee_payment_id')->nullable()->after('reverses_id');
            $table->index('legacy_fee_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex(['legacy_fee_payment_id']);
            $table->dropColumn('legacy_fee_payment_id');
        });
    }
};
