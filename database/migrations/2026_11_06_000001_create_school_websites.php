<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One public website per school: its look and on/off switch
        Schema::create('school_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('theme', 30)->default('classic');
            $table->string('color', 7)->nullable();
            $table->string('tagline', 160)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->string('footer_text', 300)->nullable();
            $table->timestamps();
        });

        // Its pages. Each page is a list of blocks (hero, text, news ...) kept as JSON
        Schema::create('school_site_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('slug', 60);
            $table->string('title', 120);
            $table->json('blocks')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_home')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_site_pages');
        Schema::dropIfExists('school_sites');
    }
};
