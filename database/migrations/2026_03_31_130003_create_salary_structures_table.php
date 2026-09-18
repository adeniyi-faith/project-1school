<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2)->default(0);
            // JSON arrays: [{"label":"House Rent","amount":2000}, ...]
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['school_id', 'staff_id'], 'salary_struct_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
