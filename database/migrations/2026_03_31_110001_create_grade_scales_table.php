<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('grade', 10);       // A+, A, B, C, D, F
            $table->decimal('gpa', 3, 2);      // 5.00, 4.00 ...
            $table->decimal('min_marks', 5, 2);
            $table->decimal('max_marks', 5, 2);
            $table->string('remarks', 50)->nullable(); // Outstanding, Excellent...
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('school_id', 'grade_scales_school_fk')->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('grade_scales'); }
};
