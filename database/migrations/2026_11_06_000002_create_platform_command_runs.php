<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Platform-level record of every one-click command the platform owner ran (not owned by any school)
    public function up(): void
    {
        Schema::create('platform_command_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command', 40)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 300)->nullable();
            $table->string('status', 12); // ok | failed
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('output')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_command_runs');
    }
};
