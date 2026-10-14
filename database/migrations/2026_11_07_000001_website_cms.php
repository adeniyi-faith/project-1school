<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The website becomes a full content system: news and events posts, a picture library, and site options.
 * It also adds the columns the second website version needed. That version changed an earlier migration that
 * live sites had already run, so those columns never reached them and saving failed; each one is added here
 * only when it is missing, so this is safe on every database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_sites', function (Blueprint $table) {
            if (! Schema::hasColumn('school_sites', 'color2')) {
                $table->string('color2', 7)->nullable();
            }
            if (! Schema::hasColumn('school_sites', 'social')) {
                $table->json('social')->nullable();
            }
            if (! Schema::hasColumn('school_sites', 'options')) {
                $table->json('options')->nullable(); // news menu label, show news in menu ...
            }
        });

        Schema::table('school_site_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('school_site_pages', 'seo_title')) {
                $table->string('seo_title', 80)->nullable();
            }
            if (! Schema::hasColumn('school_site_pages', 'seo_description')) {
                $table->string('seo_description', 200)->nullable();
            }
        });

        // the first version's look was called "classic"; it is now "heritage"
        DB::table('school_sites')->whereNotIn('theme', ['heritage', 'modern', 'warm'])->update(['theme' => 'heritage']);

        Schema::create('school_site_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('title', 160);
            $table->string('category', 30)->default('news');
            $table->string('excerpt', 300)->nullable();
            $table->text('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status', 12)->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->date('event_date')->nullable();          // for events: the day it happens
            $table->string('event_place', 160)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('seo_title', 80)->nullable();
            $table->string('seo_description', 200)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'slug']);
            $table->index(['school_id', 'status', 'published_at']);
        });

        Schema::create('school_site_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name', 200);
            $table->unsignedInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt', 200)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_site_media');
        Schema::dropIfExists('school_site_posts');
        Schema::table('school_sites', function (Blueprint $table) {
            if (Schema::hasColumn('school_sites', 'options')) {
                $table->dropColumn('options');
            }
        });
    }
};
