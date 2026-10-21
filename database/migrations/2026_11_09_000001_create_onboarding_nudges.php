<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A record of every "need a hand setting up?" nudge the platform team sent a school. Safe to run twice. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('onboarding_nudges')) {
            return;
        }

        Schema::create('onboarding_nudges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->unsignedInteger('bell_count')->default(0);
            $table->unsignedInteger('email_count')->default(0);
            $table->unsignedInteger('email_failed')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_nudges');
    }
};
