<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('issued_to_type', 50)->default('staff'); // staff / department
            $table->unsignedBigInteger('issued_to_id');
            $table->string('issued_to_name', 200)->nullable(); // denormalized for display
            $table->decimal('quantity', 10, 2);
            $table->date('issue_date');
            $table->date('return_date')->nullable();
            $table->string('purpose', 255)->nullable();
            $table->enum('status', ['issued', 'returned', 'partial'])->default('issued');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('inventory_issues'); }
};
