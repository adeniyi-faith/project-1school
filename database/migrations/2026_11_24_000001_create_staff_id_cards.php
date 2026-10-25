<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_id_cards')) {
            return;
        }

        Schema::create('staff_id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('card_no', 30);                            // e.g. STF/2026/0007
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
            $table->index(['staff_id', 'status']);
            $table->unique(['school_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_id_cards');
    }
};
