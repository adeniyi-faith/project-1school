<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id', 'subject_id'], 'marks_unique');

            $table->foreign('school_id',  'marks_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('exam_id',    'marks_exam_fk')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('student_id', 'marks_student_fk')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('subject_id', 'marks_subject_fk')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('marks'); }
};
