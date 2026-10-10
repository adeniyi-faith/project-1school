<?php

use App\Support\SchoolDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2, part 2: scores entered per score part (CA1, CA2, Exam ...), stored
 * term results with positions and averages, a draft → locked status for each
 * class's results, and behaviour/skills ratings for the report card.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One class's results for one term, and where they are in the approval steps
        Schema::create('result_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('status', 20)->default('draft'); // draft, submitted, approved, published, locked
            $table->unsignedInteger('version')->default(0);   // goes up each time results are worked out again
            $table->decimal('class_average', 5, 2)->nullable();
            $table->timestamp('computed_at')->nullable();
            foreach (['submitted', 'approved', 'published', 'locked'] as $step) {
                $table->foreignId("{$step}_by")->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp("{$step}_at")->nullable();
            }
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['term_id', 'class_id']);
        });

        // One score for one score part, e.g. Ada's CA1 in Mathematics
        Schema::create('subject_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id', 'subject_id', 'assessment_component_id'], 'subject_scores_unique');
        });

        // The worked-out result for one student in one subject, stored rather than computed on every view
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 5, 2);
            $table->string('grade', 10)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('remarks', 50)->nullable();
            $table->unsignedSmallInteger('subject_position')->nullable();
            $table->decimal('subject_average', 5, 2)->nullable();
            $table->decimal('subject_highest', 5, 2)->nullable();
            $table->decimal('subject_lowest', 5, 2)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id', 'subject_id']);
        });

        // One line per student: overall total, average and class position
        Schema::create('term_result_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('subjects_count');
            $table->decimal('total_score', 8, 2);
            $table->decimal('average', 5, 2);
            $table->unsignedSmallInteger('position')->nullable();
            $table->unsignedSmallInteger('class_size');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id']);
        });

        // Behaviour (affective) and skills (psychomotor) the school rates each term
        Schema::create('behaviour_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('domain', 20); // affective or psychomotor
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('behaviour_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('behaviour_trait_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1 (poor) to 5 (excellent)
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id', 'behaviour_trait_id'], 'behaviour_ratings_unique');
        });

        DB::table('schools')->pluck('id')->each(fn ($id) => SchoolDefaults::ensureBehaviourTraits((int) $id));
    }

    public function down(): void
    {
        Schema::dropIfExists('behaviour_ratings');
        Schema::dropIfExists('behaviour_traits');
        Schema::dropIfExists('term_result_summaries');
        Schema::dropIfExists('term_results');
        Schema::dropIfExists('subject_scores');
        Schema::dropIfExists('result_sheets');
    }
};
