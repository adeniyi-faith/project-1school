<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_audit_logs')) {
            return;
        }

        // One line for each thing a member of the platform team did. Lines are only ever added, never changed or removed.
        Schema::create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            // Copied at the time, so the line still reads right if the person is renamed or removed
            $table->string('user_name', 255)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('role', 60)->nullable();
            // login, logout, enter_school, leave_school, changed, viewed ...
            $table->string('action', 40)->index();
            $table->string('description', 500);
            // The school the person was inside (not named school_id on purpose: this table belongs to the platform)
            $table->unsignedBigInteger('acting_school_id')->nullable()->index();
            $table->string('acting_school_name', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('route', 120)->nullable();
            $table->string('path', 500)->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
    }
};
