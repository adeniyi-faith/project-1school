<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A website page can sit under another page in the menu (a sub-menu). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_site_pages', 'parent_id')) {
            Schema::table('school_site_pages', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('position')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_site_pages', 'parent_id')) {
            Schema::table('school_site_pages', function (Blueprint $table) {
                $table->dropColumn('parent_id');
            });
        }
    }
};
