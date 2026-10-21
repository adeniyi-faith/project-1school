<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Notices the platform team sends to schools: a banner, a bell notice, an email, or any mix. Safe to run twice. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_announcements')) {
            return;
        }

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('body');
            $table->string('kind', 12)->default('notice');      // notice, maintenance
            $table->string('audience', 10)->default('all');     // all, package, schools
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->json('school_ids')->nullable();
            $table->boolean('show_banner')->default(true);
            $table->boolean('send_bell')->default(false);
            $table->boolean('send_email')->default(false);
            $table->timestamp('starts_at')->nullable();         // notice: show from. maintenance: when the work starts
            $table->timestamp('ends_at')->nullable();           // notice: show until. maintenance: when the work ends
            $table->string('status', 10)->default('draft');     // draft, published, ended
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('bell_count')->default(0);
            $table->unsignedInteger('email_count')->default(0);
            $table->unsignedInteger('email_failed')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
    }
};
