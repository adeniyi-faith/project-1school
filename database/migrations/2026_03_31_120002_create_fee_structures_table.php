<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->cascadeOnDelete();
            $table->string('academic_year', 20)->default('2025-2026');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->enum('frequency', ['monthly', 'quarterly', 'annual', 'one_time'])->default('monthly');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index(['school_id', 'class_id', 'academic_year'], 'fee_struct_class_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
