<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('school_id', 'holidays_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->index('school_id', 'holidays_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
