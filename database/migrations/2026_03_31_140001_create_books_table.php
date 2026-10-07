<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('isbn', 20)->nullable();
            $table->string('title');
            $table->string('author', 200);
            $table->string('category', 100)->nullable();
            $table->string('publisher', 200)->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('location', 100)->nullable();   // shelf/rack
            $table->unsignedSmallInteger('total_copies')->default(1);
            $table->unsignedSmallInteger('available_copies')->default(1);
            $table->string('cover')->nullable();            // file path
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index(['school_id', 'isbn'], 'books_isbn_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
