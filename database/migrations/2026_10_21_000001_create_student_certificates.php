<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School leaving certificates: testimonials and transfer certificates. Each one keeps a copy
 * of what was printed, so a reprint always matches the original, and has a public code that
 * anyone can check on the school's site to confirm it is genuine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                  // testimonial | transfer
            $table->unsignedInteger('number');           // 1, 2, 3 ... per school and type
            $table->string('serial', 40);                // TST/2026/0001
            $table->string('verify_code', 16)->unique(); // for the public check page
            $table->date('issued_on');
            $table->json('details');                     // everything printed on the certificate
            $table->foreignId('signer_id')->nullable()->constrained('report_card_signers')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['school_id', 'type', 'number']);
            $table->index(['school_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_certificates');
    }
};
