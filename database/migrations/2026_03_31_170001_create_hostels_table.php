<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 150);
            $table->enum('type', ['boys', 'girls', 'mixed'])->default('boys');
            $table->foreignId('warden_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('address')->nullable();
            $table->unsignedSmallInteger('total_rooms')->default(0);
            $table->unsignedSmallInteger('total_capacity')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('hostels'); }
};
