<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Computer-based tests: question banks, tests drawn from them, and each person's sitting */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name', 120); // e.g. JSS 1 Mathematics, Entrance English
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cbt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('cbt_bank_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 120)->nullable();
            $table->string('difficulty', 10)->default('medium'); // easy, medium, hard
            $table->text('text');
            $table->string('image_path')->nullable();
            $table->json('options'); // list of answer choices
            $table->unsignedTinyInteger('correct_option'); // index into options
            $table->text('explanation')->nullable();
            $table->decimal('marks', 5, 2)->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['cbt_bank_id', 'topic', 'difficulty']);
        });

        Schema::create('cbt_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('audience', 12)->default('students'); // students, applicants
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at')->nullable();
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_score')->default(true);
            $table->string('access_code', 12)->nullable(); // given out in the exam hall
            $table->unsignedTinyInteger('max_page_leaves')->nullable(); // hand in automatically after this many
            $table->decimal('pass_mark', 5, 2)->nullable(); // percent
            $table->string('status', 12)->default('draft'); // draft, published, closed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_id', 'status']);
        });

        // Each section draws questions from one bank, optionally only one topic or difficulty
        Schema::create('cbt_test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbt_test_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title', 120);
            $table->foreignId('cbt_bank_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 120)->nullable();
            $table->string('difficulty', 10)->nullable();
            $table->unsignedSmallInteger('question_count');
        });

        Schema::create('cbt_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('cbt_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('deadline_at');
            $table->unsignedSmallInteger('extra_minutes')->default(0);
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress, submitted, timed_out
            $table->string('ended_reason', 30)->nullable(); // left_page when handed in for leaving the page
            $table->json('paper'); // the questions this person got: question, option order, section
            $table->json('answers')->nullable(); // question id => chosen option (original index)
            $table->json('flags')->nullable(); // question ids marked for review
            $table->unsignedSmallInteger('page_leaves')->default(0);
            $table->json('marking')->nullable(); // question id => chosen, correct, marks; fixed when marked
            $table->json('section_scores')->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('max_score', 7, 2)->nullable();
            $table->timestamps();
            $table->unique(['cbt_test_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cbt_attempts');
        Schema::dropIfExists('cbt_test_sections');
        Schema::dropIfExists('cbt_tests');
        Schema::dropIfExists('cbt_questions');
        Schema::dropIfExists('cbt_banks');
    }
};
