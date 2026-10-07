<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_note')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index(['school_id', 'staff_id'], 'leave_req_staff_idx');
            $table->index(['school_id', 'status'], 'leave_req_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
