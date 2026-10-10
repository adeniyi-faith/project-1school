<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'shifts_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->index('school_id', 'shifts_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
