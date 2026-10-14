<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam scope: a test section can draw from several topics (the ones taught), and a printed exam paper
 * remembers which topics it was set from. Each column is only added when it is missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cbt_test_sections', 'topics')) {
            Schema::table('cbt_test_sections', function (Blueprint $table) {
                $table->json('topics')->nullable()->after('topic');
            });
        }
        if (! Schema::hasColumn('exam_papers', 'scope')) {
            Schema::table('exam_papers', function (Blueprint $table) {
                $table->json('scope')->nullable()->after('show_marks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cbt_test_sections', 'topics')) {
            Schema::table('cbt_test_sections', fn (Blueprint $table) => $table->dropColumn('topics'));
        }
        if (Schema::hasColumn('exam_papers', 'scope')) {
            Schema::table('exam_papers', fn (Blueprint $table) => $table->dropColumn('scope'));
        }
    }
};
