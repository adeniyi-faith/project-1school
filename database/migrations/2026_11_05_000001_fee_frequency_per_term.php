<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A fee's "how often" was a fixed list that began with Monthly. Schools bill per term, so "per_term" is added and
 * becomes the default. The column becomes plain text so the list can grow; annual and one-time fees stay as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fee_structures DROP CONSTRAINT IF EXISTS fee_structures_frequency_check');
        }

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->string('frequency', 20)->default('per_term')->change();
        });

        // Nothing in billing reads this value (invoices are made per term), so old Monthly and Quarterly labels
        // would only mislead; they become "per term". Amounts are not touched.
        DB::table('fee_structures')->whereIn('frequency', ['monthly', 'quarterly'])->update(['frequency' => 'per_term']);
    }

    public function down(): void
    {
        // Fees set to "per term" go back to "quarterly", the closest of the old choices
        DB::table('fee_structures')->where('frequency', 'per_term')->update(['frequency' => 'quarterly']);
    }
};
