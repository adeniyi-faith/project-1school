<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Websites now start switched on, so the ones made before this change are switched on too. A school can switch its site off in Website > Settings. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('school_sites')->where('enabled', false)->update(['enabled' => true]);
    }

    public function down(): void
    {
        // not undone: we cannot tell which sites a school switched off on purpose
    }
};
