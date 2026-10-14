<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Photo albums for a school's public website. Photos come from the website picture library. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_site_albums')) {
            Schema::create('school_site_albums', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('slug', 120);
                $table->string('title', 160);
                $table->text('description')->nullable();
                $table->date('event_date')->nullable();
                $table->unsignedBigInteger('cover_media_id')->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['school_id', 'slug']);
            });
        }

        if (! Schema::hasTable('school_site_album_media')) {
            Schema::create('school_site_album_media', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('album_id')->index();
                $table->unsignedBigInteger('media_id')->index();
                $table->string('caption', 200)->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
                $table->unique(['album_id', 'media_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_site_album_media');
        Schema::dropIfExists('school_site_albums');
    }
};
