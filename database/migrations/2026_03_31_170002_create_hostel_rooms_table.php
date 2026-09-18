<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->string('room_no', 20);
            $table->string('floor', 20)->nullable();
            $table->enum('type', ['single', 'double', 'dormitory'])->default('double');
            $table->unsignedSmallInteger('capacity')->default(2);
            $table->unsignedSmallInteger('occupied')->default(0);
            $table->boolean('ac')->default(false);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->enum('status', ['available', 'full', 'maintenance'])->default('available');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
            $table->unique(['hostel_id', 'room_no']);
        });
    }

    public function down(): void { Schema::dropIfExists('hostel_rooms'); }
};
