<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('module_slug', 60);
            $table->boolean('is_enabled')->default(true);
            $table->foreign('school_id', 'smod_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->unique(['school_id', 'module_slug'], 'smod_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_modules');
    }
};
