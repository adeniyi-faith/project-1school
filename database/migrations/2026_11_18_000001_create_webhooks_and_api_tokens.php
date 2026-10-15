<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_webhooks')) {
            Schema::create('school_webhooks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('url', 500);
                $table->text('secret');
                $table->json('events')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('last_status')->nullable();
                $table->string('last_error', 300)->nullable();
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();
                $table->index('school_id');
            });
        }

        if (! Schema::hasTable('school_api_tokens')) {
            Schema::create('school_api_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80);
                $table->string('token_hash', 64)->unique();
                $table->string('last_four', 4);
                $table->foreignId('created_by')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->index('school_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_api_tokens');
        Schema::dropIfExists('school_webhooks');
    }
};
