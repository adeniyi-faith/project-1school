<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('school_id',  'student_docs_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('student_id', 'student_docs_student_fk')->references('id')->on('students')->cascadeOnDelete();
            $table->index('student_id', 'student_docs_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
