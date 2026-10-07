<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->string('title', 255);
            $table->string('file', 500)->nullable();
            $table->json('topics')->nullable(); // [{title, covered: bool}]
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('syllabi'); }
};
