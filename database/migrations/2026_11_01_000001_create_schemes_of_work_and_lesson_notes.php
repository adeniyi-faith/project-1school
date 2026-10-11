<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schemes of work (a subject's topics week by week for a term), lesson note formats (the headings a
 * school wants in every note), and lesson notes, which are the existing lesson plans with those headings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes_of_work', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->string('title');
            // The file the school uploaded (private disk), kept so it can be opened again
            $table->string('source_path', 500)->nullable();
            $table->string('source_name')->nullable();
            // [{week: 1, topic: "...", details: "..."}]
            $table->json('weeks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
            $table->index(['school_id', 'class_id', 'subject_id']);
        });

        Schema::create('lesson_note_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 120);
            // [{key: "objectives", label: "Behavioural objectives", hint: "..."}]
            $table->json('sections');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index('school_id');
        });

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('teacher_id')->constrained('terms')->nullOnDelete();
            $table->foreignId('scheme_of_work_id')->nullable()->after('term_id')->constrained('schemes_of_work')->nullOnDelete();
            $table->foreignId('lesson_note_format_id')->nullable()->after('scheme_of_work_id')->constrained('lesson_note_formats')->nullOnDelete();
            $table->unsignedTinyInteger('week')->nullable()->after('week_start');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('week');
            // The note itself, one text per heading of its format: {"objectives": "...", ...}
            $table->json('sections')->nullable()->after('resources');
            $table->boolean('ai_drafted')->default(false)->after('sections');
            $table->foreignId('created_by')->nullable()->after('ai_drafted')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_id');
            $table->dropConstrainedForeignId('scheme_of_work_id');
            $table->dropConstrainedForeignId('lesson_note_format_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['week', 'duration_minutes', 'sections', 'ai_drafted']);
        });
        Schema::dropIfExists('lesson_note_formats');
        Schema::dropIfExists('schemes_of_work');
    }
};
