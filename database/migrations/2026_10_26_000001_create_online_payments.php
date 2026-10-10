<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fee payments parents start online (Paystack). The money only counts once Paystack confirms it and a ledger line is written. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway', 20)->default('paystack');
            $table->string('reference', 60)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('email');
            $table->string('status', 20)->default('pending');   // pending, success, failed, needs_review
            $table->string('channel', 30)->nullable();           // card, bank_transfer, ussd ...
            $table->string('problem')->nullable();               // why it needs review
            $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
    }
};
