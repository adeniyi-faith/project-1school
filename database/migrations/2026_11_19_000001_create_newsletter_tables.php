<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('newsletter_subscribers')) {
            Schema::create('newsletter_subscribers', function (Blueprint $t) {
                $t->id();
                $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
                $t->string('email', 150);
                $t->string('name', 100)->nullable();
                $t->string('status', 20)->default('pending')->index(); // pending, confirmed, unsubscribed
                $t->json('interests')->nullable();
                $t->string('confirm_token', 64)->unique();
                $t->string('unsubscribe_token', 64)->unique();
                $t->string('consent_text', 300)->nullable();
                $t->timestamp('consented_at')->nullable();
                $t->string('consent_ip', 45)->nullable();
                $t->string('consent_page', 300)->nullable();
                $t->timestamp('confirmed_at')->nullable();
                $t->timestamp('unsubscribed_at')->nullable();
                $t->timestamp('confirm_sent_at')->nullable();
                $t->timestamps();
                $t->unique(['school_id', 'email']);
            });
        }

        if (! Schema::hasTable('newsletter_campaigns')) {
            Schema::create('newsletter_campaigns', function (Blueprint $t) {
                $t->id();
                $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
                $t->string('subject', 150);
                $t->string('preheader', 150)->nullable();
                $t->text('body');
                $t->string('audience', 30)->default('all');
                $t->string('status', 20)->default('draft')->index(); // draft, sending, sent
                $t->unsignedInteger('total')->default(0);
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('started_at')->nullable();
                $t->timestamp('finished_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('newsletter_deliveries')) {
            Schema::create('newsletter_deliveries', function (Blueprint $t) {
                $t->id();
                $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
                $t->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
                $t->foreignId('subscriber_id')->constrained('newsletter_subscribers')->cascadeOnDelete();
                $t->string('email', 150);
                $t->string('status', 20)->default('queued')->index(); // queued, sending, sent, failed, unsure, skipped
                $t->string('error', 300)->nullable();
                $t->timestamp('sent_at')->nullable();
                $t->timestamps();
                $t->unique(['campaign_id', 'subscriber_id']); // one email per person per newsletter, whatever happens
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_deliveries');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
    }
};
