<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('title', 255);
            $table->enum('platform', ['zoom', 'google_meet', 'jitsi', 'other'])->default('zoom');
            $table->string('meeting_url', 500);
            $table->string('meeting_id', 100)->nullable();
            $table->string('passcode', 50)->nullable();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('online_classes'); }
};
