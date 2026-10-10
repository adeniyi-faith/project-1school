<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entrance exams and interviews for an admission inquiry, the accept/decline decision,
 * and who enrolled the child. Inquiry status becomes a plain string so it can take
 * the new "accepted" step (new → follow_up → accepted → admitted, or dropped).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->constrained('admission_inquiries')->cascadeOnDelete();
            $table->string('type', 12);                       // exam, interview
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue', 150)->nullable();
            $table->decimal('score', 6, 2)->nullable();       // exams only
            $table->decimal('max_score', 6, 2)->nullable();
            $table->string('outcome', 12)->default('pending'); // pending, passed, failed, absent
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['inquiry_id', 'type']);
        });

        // Postgres keeps the old enum as a CHECK constraint; drop it so "accepted" is allowed
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE admission_inquiries DROP CONSTRAINT IF EXISTS admission_inquiries_status_check');
        }

        Schema::table('admission_inquiries', function (Blueprint $table) {
            $table->string('status', 20)->default('new')->change();
            $table->foreignId('decided_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decided_by');
            $table->string('decision_note', 500)->nullable()->after('decided_at');
            $table->foreignId('enrolled_by')->nullable()->after('converted_student_id')->constrained('users')->nullOnDelete();
            $table->timestamp('enrolled_at')->nullable()->after('enrolled_by');
        });
    }

    public function down(): void
    {
        Schema::table('admission_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropConstrainedForeignId('enrolled_by');
            $table->dropColumn(['decided_at', 'decision_note', 'enrolled_at']);
        });
        // Inquiries that were accepted but not enrolled go back to "follow up"; the old list had no "accepted"
        DB::table('admission_inquiries')->where('status', 'accepted')->update(['status' => 'follow_up']);
        Schema::dropIfExists('admission_assessments');
    }
};
