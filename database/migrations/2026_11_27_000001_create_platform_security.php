<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_security')) {
            return;
        }

        // One row per member of the platform team: their second sign-in step and whether they must pick a new password.
        Schema::create('platform_security', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->text('totp_secret')->nullable();               // encrypted; set while setting up
            $table->timestamp('totp_confirmed_at')->nullable();    // set once the first code was typed correctly
            $table->unsignedBigInteger('totp_last_step')->nullable(); // newest 30-second step used, so a code works once
            $table->text('recovery_codes')->nullable();            // encrypted JSON list of scrambled one-time codes
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_security');
    }
};
