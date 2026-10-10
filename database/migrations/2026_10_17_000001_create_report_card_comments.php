<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The class teacher's and principal's written comments on each student's term report card. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('teacher_comment')->nullable();
            $table->foreignId('teacher_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('principal_comment')->nullable();
            $table->foreignId('principal_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_comments');
    }
};
