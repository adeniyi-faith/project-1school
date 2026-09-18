<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('member_type');
            $table->unsignedBigInteger('member_id');
            $table->date('reserved_date');
            $table->enum('status', ['waiting', 'fulfilled', 'cancelled'])->default('waiting');
            $table->timestamps();

            $table->index('school_id');
            $table->index(['book_id', 'status'], 'book_res_book_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reservations');
    }
};
