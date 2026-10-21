<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Platform-level list of slow database questions (the SQL shape only, never the values); not owned by any school
    public function up(): void
    {
        Schema::create('slow_queries', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 32)->index();
            $table->text('sql');
            $table->unsignedInteger('time_ms');
            $table->string('path', 200)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slow_queries');
    }
};
