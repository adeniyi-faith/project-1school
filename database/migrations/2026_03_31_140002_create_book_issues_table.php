<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('member_type');          // App\Models\Student | App\Models\Staff
            $table->unsignedBigInteger('member_id');
            $table->date('issued_date');
            $table->date('due_date');
            $table->date('returned_date')->nullable();
            $table->decimal('fine', 8, 2)->default(0);
            $table->decimal('fine_per_day', 6, 2)->default(2.00);
            $table->enum('status', ['issued', 'returned', 'overdue'])->default('issued');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['member_type', 'member_id'], 'book_issues_member_idx');
            $table->index(['school_id', 'status'], 'book_issues_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_issues');
    }
};
