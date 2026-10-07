<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly',  10, 2)->default(0);
            $table->unsignedInteger('max_students')->default(0);
            $table->unsignedInteger('max_staff')->default(0);
            $table->unsignedInteger('storage_gb')->default(5);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
