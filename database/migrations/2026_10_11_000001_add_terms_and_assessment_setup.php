<?php

use App\Support\SchoolDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2, part 1: terms inside each school year, score parts (CA1, CA2, Exam ...)
 * and named grade scales that a school admin can change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedTinyInteger('sequence');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->unique(['academic_year_id', 'sequence']);
        });

        // A named set of score parts, e.g. "CA1 20 + CA2 20 + Exam 60"
        Schema::create('assessment_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_scheme_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('short_name', 10);
            $table->decimal('max_score', 5, 2);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('school_id');
        });

        // A named grade scale, e.g. "WAEC (A1–F9)"; its bands stay in grade_scales
        Schema::create('grading_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::table('grade_scales', function (Blueprint $table) {
            $table->foreignId('grading_scheme_id')->nullable()->after('school_id')->constrained()->cascadeOnDelete();
        });

        // Each class may use its own setup; empty means "use the school default"
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('assessment_scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grading_scheme_id')->nullable()->constrained()->nullOnDelete();
        });

        // A subject may override its class, e.g. Practical Physics with a practical part
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('assessment_scheme_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('class_id')->constrained()->nullOnDelete();
        });

        // Give every existing school its terms, a default score setup and a grade scale.
        // Grades a school already typed in are kept as its default scale.
        DB::table('schools')->pluck('id')->each(fn ($id) => SchoolDefaults::apply((int) $id));
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_id');
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_scheme_id');
        });
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grading_scheme_id');
            $table->dropConstrainedForeignId('assessment_scheme_id');
        });
        Schema::table('grade_scales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grading_scheme_id');
        });
        Schema::dropIfExists('grading_schemes');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('assessment_schemes');
        Schema::dropIfExists('terms');
    }
};
