<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('staff', 'show_on_website')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->boolean('show_on_website')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff', 'show_on_website')) {
            Schema::table('staff', fn (Blueprint $table) => $table->dropColumn('show_on_website'));
        }
    }
};
