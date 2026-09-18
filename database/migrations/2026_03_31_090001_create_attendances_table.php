<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('academic_year_id')->nullable()->index();
            $table->date('date')->index();
            $table->string('attendable_type');
            $table->unsignedBigInteger('attendable_id');
            $table->enum('status', ['present', 'absent', 'late', 'half_day'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['attendable_type', 'attendable_id']);
            $table->unique(['school_id', 'date', 'attendable_type', 'attendable_id'], 'attendances_unique');

            $table->foreign('school_id', 'attendances_school_fk')
                  ->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
