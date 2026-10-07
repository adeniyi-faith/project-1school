<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('homework_id')->constrained('homework')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('file', 500)->nullable();
            $table->text('text_response')->nullable();
            $table->enum('status', ['submitted', 'reviewed', 'returned'])->default('submitted');
            $table->text('teacher_remarks')->nullable();
            $table->timestamps();
            $table->unique(['homework_id', 'student_id']);
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('homework_submissions'); }
};
