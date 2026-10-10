<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A record of every SMS and email blast, with one line per person and whether it went */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('channel', 10); // sms or email
            $table->string('audience', 120); // who it was for, in words
            $table->string('subject')->nullable();
            $table->text('body');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('message_batch_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_type', 20); // parent, student, staff
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name', 150);
            $table->string('to', 150)->nullable(); // phone number or email address
            $table->string('status', 20)->default('queued'); // queued, sent, failed, not_sent
            $table->string('error', 300)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['message_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_deliveries');
        Schema::dropIfExists('message_batches');
    }
};
