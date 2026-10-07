<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('registration_no', 50);
            $table->string('name', 100)->nullable(); // e.g. "Bus 01"
            $table->enum('type', ['bus', 'minibus', 'van', 'car', 'other'])->default('bus');
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->string('driver_name', 150)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('helper_name', 150)->nullable();
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('vehicles'); }
};
