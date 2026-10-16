<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_forms')) {
            Schema::create('site_forms', function (Blueprint $t) {
                $t->id();
                $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
                $t->string('name', 120);
                $t->string('intro', 400)->nullable();
                $t->json('fields');
                $t->json('consents')->nullable();
                $t->string('button_text', 40)->default('Send');
                $t->string('success_message', 300)->nullable();
                $t->string('notify_email', 150)->nullable();
                $t->boolean('newsletter_optin')->default(false);
                $t->boolean('active')->default(true);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('site_form_submissions')) {
            Schema::create('site_form_submissions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
                $t->foreignId('form_id')->index()->constrained('site_forms')->cascadeOnDelete();
                $t->string('form_name', 120);
                $t->json('answers');
                $t->json('consents')->nullable();
                $t->boolean('newsletter_asked')->default(false);
                $t->string('ip', 45)->nullable();
                $t->string('page', 300)->nullable();
                $t->string('status', 10)->default('new')->index(); // new, read
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_form_submissions');
        Schema::dropIfExists('site_forms');
    }
};
