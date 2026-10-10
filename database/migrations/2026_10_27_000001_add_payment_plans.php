<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paying fees in parts. A payment plan is a saved pattern (e.g. "Two parts: 60% at resumption, 40% by
 * mid-term"); putting it on an invoice gives the invoice dated parts. What each part has received is
 * worked out from the ledger every time the ledger changes, so the money itself stays in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->json('parts');                       // [{label, percent}], percents add up to 100
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('invoice_instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('label', 60);
            $table->decimal('percent', 5, 2);
            $table->date('due_date');
            $table->decimal('amount', 12, 2)->default(0);   // its share of what the invoice comes to now
            $table->decimal('paid', 12, 2)->default(0);     // payments so far, filled from the first part on
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['invoice_id', 'position']);
            $table->index('due_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_plan_id')->nullable()->after('due_date')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_plan_id');
        });
        Schema::dropIfExists('invoice_instalments');
        Schema::dropIfExists('payment_plans');
    }
};
