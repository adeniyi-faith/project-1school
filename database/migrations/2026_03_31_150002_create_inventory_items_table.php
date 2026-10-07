<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('unit', 30)->default('pcs'); // pcs / kg / litre / box / ream
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(5);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('inventory_items'); }
};
