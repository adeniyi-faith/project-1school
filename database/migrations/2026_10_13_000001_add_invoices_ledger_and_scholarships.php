<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2, part 3: invoices (what a student owes), an append-only ledger
 * (every charge, payment, discount and correction as its own line), and
 * named scholarships that someone approves.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A named discount policy, e.g. "Staff child – 50% tuition"
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 10);                 // percent or fixed
            $table->decimal('value', 12, 2);            // 50 (%) or 20000 (naira)
            $table->foreignId('fee_category_id')->nullable()->constrained()->nullOnDelete(); // empty = every fee
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        // A scholarship given to one student, and who approved it
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['student_id', 'scholarship_id']);
        });

        // One bill: one student, one fee, one period (e.g. "2026/2027 · First Term")
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 30)->nullable();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 60);
            $table->decimal('amount', 12, 2);           // the fee when the invoice was made
            $table->date('due_date')->nullable();
            $table->string('status', 10)->default('unpaid'); // unpaid, partial, paid, void (kept in step with the ledger)
            $table->decimal('balance', 12, 2)->default(0);   // kept in step with the ledger
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['school_id', 'invoice_no']);
            $table->unique(['student_id', 'fee_structure_id', 'period'], 'invoices_once_per_period');
        });

        // Append-only: lines are never edited or deleted. A mistake is fixed with a reversal line.
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12);                 // charge, fine, payment, discount, reversal
            $table->decimal('amount', 12, 2);           // + adds to what is owed, − takes away
            $table->string('method', 30)->nullable();   // for payments: cash, bank_transfer, pos ...
            $table->string('reference', 60)->nullable(); // receipt number or bank reference
            $table->date('entry_date');
            $table->string('note', 500)->nullable();
            $table->foreignId('student_scholarship_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reverses_id')->nullable()->constrained('ledger_entries')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('school_id');
            $table->index(['invoice_id', 'type']);
            $table->unique('reverses_id'); // a line can only be reversed once
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('student_scholarships');
        Schema::dropIfExists('scholarships');
    }
};
