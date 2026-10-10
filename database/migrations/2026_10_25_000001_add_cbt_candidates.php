<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Applicants sitting an entrance exam on the computer: each gets an exam number and a PIN instead of an account */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cbt_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cbt_test_id')->constrained('cbt_tests')->cascadeOnDelete();
            $table->foreignId('inquiry_id')->constrained('admission_inquiries')->cascadeOnDelete();
            $table->foreignId('admission_assessment_id')->nullable()->constrained('admission_assessments')->nullOnDelete();
            $table->string('exam_number', 20)->unique();
            $table->text('pin');                              // kept encrypted so slips can be printed again
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['cbt_test_id', 'inquiry_id']);
        });

        Schema::table('cbt_attempts', function (Blueprint $table) {
            $table->foreignId('cbt_candidate_id')->nullable()->after('student_id')->constrained('cbt_candidates')->cascadeOnDelete();
            $table->unique(['cbt_test_id', 'cbt_candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cbt_attempts', function (Blueprint $table) {
            $table->dropUnique(['cbt_test_id', 'cbt_candidate_id']);
            $table->dropConstrainedForeignId('cbt_candidate_id');
        });
        Schema::dropIfExists('cbt_candidates');
    }
};
