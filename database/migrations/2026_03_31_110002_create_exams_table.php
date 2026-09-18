<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('class_id')->index();
            $table->string('name', 150);
            $table->enum('type', ['unit_test', 'mid_term', 'final', 'custom'])->default('mid_term');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'exams_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id',  'exams_class_fk')->references('id')->on('classes')->cascadeOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('exams'); }
};
