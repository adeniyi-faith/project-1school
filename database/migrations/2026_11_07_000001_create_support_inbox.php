<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Support inbox: a school raises a request, the platform team replies. Each step checks first, so it is safe to run twice. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // who raised it
                $table->string('subject', 150);
                $table->string('category', 20)->default('question'); // question, problem, billing, request
                $table->string('priority', 10)->default('normal');   // normal, urgent
                $table->string('status', 10)->default('open');       // open (waiting for us), answered (waiting for the school), closed
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index('school_id');
                $table->index(['status', 'last_reply_at']);
            });
        }

        if (! Schema::hasTable('support_messages')) {
            Schema::create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('from_staff')->default(false);
                $table->boolean('internal')->default(false); // a note between platform staff; the school never sees it
                $table->text('body');
                $table->timestamps();

                $table->index('school_id');
                $table->index('support_ticket_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
