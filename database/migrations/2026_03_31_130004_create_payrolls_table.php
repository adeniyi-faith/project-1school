<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('month_year', 10);          // e.g. "2026-01"
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2);
            $table->unsignedSmallInteger('working_days')->default(26);
            $table->unsignedSmallInteger('present_days')->default(26);
            $table->unsignedSmallInteger('leave_days')->default(0);
            $table->json('allowances_snapshot')->nullable();
            $table->json('deductions_snapshot')->nullable();
            $table->enum('status', ['draft', 'generated', 'paid'])->default('draft');
            $table->date('paid_on')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['school_id', 'staff_id', 'month_year'], 'payroll_staff_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
