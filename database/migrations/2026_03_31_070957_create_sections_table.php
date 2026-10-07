<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->string('name');
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'sections_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id',  'sections_class_fk')->references('id')->on('classes')->cascadeOnDelete();
            $table->index('school_id', 'sections_school_idx');
            $table->index('class_id',  'sections_class_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
