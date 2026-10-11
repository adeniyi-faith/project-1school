<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student ID cards. A design says how cards look (portrait or landscape, style, colours, what is printed);
 * a card is one student's numbered card with a secret code in its QR. Scanning the QR finds the student,
 * so a card can be used to mark attendance or check fees. A lost card is replaced, which stops the old QR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_card_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('orientation', 10)->default('portrait');   // portrait or landscape
            $table->string('template', 20)->default('classic');       // classic, modern, band
            $table->string('primary_color', 7)->nullable();           // empty = the report card colours
            $table->string('accent_color', 7)->nullable();
            $table->string('title', 60)->default('STUDENT ID CARD');
            $table->json('show')->nullable();                         // which details are printed
            $table->text('back_text')->nullable();                    // e.g. "If found, please return to ..."
            $table->foreignId('signer_id')->nullable()->constrained('report_card_signers')->nullOnDelete();
            $table->unsignedSmallInteger('valid_months')->nullable(); // empty = until the end of the school year
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('student_id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('card_no', 30);                            // e.g. ID/2026/0042
            $table->string('token', 40)->unique();                    // what the QR carries; nothing personal
            $table->string('status', 10)->default('active');          // active, replaced, revoked
            $table->date('expires_on')->nullable();
            $table->string('note', 200)->nullable();                  // why it was replaced or stopped
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedSmallInteger('prints')->default(0);
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['student_id', 'status']);
            $table->unique(['school_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_cards');
        Schema::dropIfExists('id_card_designs');
    }
};
