<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->unsignedInteger('numeric_name')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id',        'classes_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_teacher_id', 'classes_teacher_fk')->references('id')->on('users')->nullOnDelete();
            $table->index('school_id', 'classes_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
