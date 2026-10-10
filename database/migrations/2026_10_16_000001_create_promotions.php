<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * End-of-year moves. A batch is one "move this class up" action; each student in it gets a
 * line saying where they were, where they went (promoted, repeated or graduated) and why.
 * The lines are the student's class history, and let a batch be undone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('from_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('to_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->decimal('pass_mark', 5, 2)->nullable();
            $table->unsignedInteger('promoted_count')->default(0);
            $table->unsignedInteger('repeated_count')->default(0);
            $table->unsignedInteger('graduated_count')->default(0);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['academic_year_id', 'from_class_id']);
        });

        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_batch_id')->constrained('promotion_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('outcome', 12);                     // promoted, repeated, graduated
            $table->foreignId('from_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('from_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('from_status', 20)->default('active');
            $table->foreignId('to_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('to_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->decimal('year_average', 6, 2)->nullable();  // average of the year's term results, when there were any
            $table->text('reason')->nullable();                 // required when a student repeats
            $table->timestamps();

            $table->index('school_id');
            $table->index(['student_id', 'promotion_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('promotion_batches');
    }
};
