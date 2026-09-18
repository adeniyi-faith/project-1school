<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->string('receipt_no', 30)->unique();
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('fine', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('month_year', 10)->nullable();  // e.g. "2026-01" for monthly fees
            $table->enum('method', ['cash', 'card', 'online', 'bkash', 'nagad', 'rocket'])->default('cash');
            $table->enum('status', ['paid', 'partial', 'pending', 'overdue'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index(['school_id', 'student_id'], 'fee_pay_student_idx');
            $table->index(['school_id', 'status'], 'fee_pay_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
