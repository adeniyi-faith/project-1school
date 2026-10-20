<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The product is now called Adino School Systems. A platform name still saved as the old
 * default ("SchoolRuns") is switched over; a name the owner chose for themselves is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings') || ! Schema::hasColumn('platform_settings', 'value')) {
            return;
        }

        DB::table('platform_settings')
            ->where('key', 'platform_name')
            ->where('value', 'SchoolRuns')
            ->update(['value' => 'Adino School Systems']);
    }

    public function down(): void
    {
        // Nothing to undo: the old name was only a default.
    }
};
