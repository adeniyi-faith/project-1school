<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('type', ['theory', 'practical'])->default('theory');
            $table->unsignedInteger('full_marks')->default(100);
            $table->unsignedInteger('pass_marks')->default(33);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'subjects_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id',  'subjects_class_fk')->references('id')->on('classes')->cascadeOnDelete();
            $table->index('school_id', 'subjects_school_idx');
            $table->index('class_id',  'subjects_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
