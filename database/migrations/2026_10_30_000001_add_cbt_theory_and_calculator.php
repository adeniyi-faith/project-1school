<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Theory questions in CBT: the student types an answer and a teacher marks it with the help of a marking guide.
 * Also an on-screen calculator a test can switch on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->string('type', 20)->default('objective')->after('cbt_bank_id');
            $table->text('marking_guide')->nullable()->after('explanation');
            // Theory questions have no options and no right option
            $table->json('options')->nullable()->change();
            $table->unsignedTinyInteger('correct_option')->nullable()->change();
        });

        Schema::table('cbt_test_sections', function (Blueprint $table) {
            // null: any question in the bank; objective or theory: only that kind
            $table->string('question_type', 20)->nullable()->after('difficulty');
        });

        Schema::table('cbt_tests', function (Blueprint $table) {
            $table->boolean('allow_calculator')->default(false)->after('show_score');
        });

        Schema::table('cbt_attempts', function (Blueprint $table) {
            $table->json('written')->nullable()->after('answers');
            // Handed in, but a teacher still has theory answers to mark
            $table->boolean('needs_marking')->default(false)->after('marking');
            $table->timestamp('marked_at')->nullable()->after('needs_marking');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_attempts', fn (Blueprint $table) => $table->dropColumn(['written', 'needs_marking', 'marked_at']));
        Schema::table('cbt_tests', fn (Blueprint $table) => $table->dropColumn('allow_calculator'));
        Schema::table('cbt_test_sections', fn (Blueprint $table) => $table->dropColumn('question_type'));
        // Theory questions cannot live without the new columns. options and correct_option stay nullable,
        // which does no harm to objective questions.
        \Illuminate\Support\Facades\DB::table('cbt_questions')->where('type', 'theory')->delete();
        Schema::table('cbt_questions', fn (Blueprint $table) => $table->dropColumn(['type', 'marking_guide']));
    }
};
