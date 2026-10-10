<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('asset_code', 50)->nullable();
            $table->string('category', 100)->nullable(); // Furniture / Electronics / Vehicle / Other
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('current_value', 12, 2)->default(0);
            $table->enum('depreciation_method', ['straight_line', 'reducing_balance'])->default('straight_line');
            $table->decimal('depreciation_rate', 5, 2)->default(10); // % per year
            $table->string('location', 200)->nullable();
            $table->string('assigned_to', 200)->nullable();
            $table->enum('status', ['active', 'disposed', 'maintenance'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('assets'); }
};
