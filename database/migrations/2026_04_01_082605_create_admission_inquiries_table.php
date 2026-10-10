<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('student_name', 150);
            $table->string('class_interested', 50);
            $table->string('guardian_name', 150);
            $table->string('guardian_phone', 25);
            $table->string('guardian_email', 150)->nullable();
            $table->enum('status', ['new', 'follow_up', 'admitted', 'dropped'])->default('new');
            $table->text('notes')->nullable();
            $table->date('next_followup_date')->nullable();
            $table->string('source', 50)->default('walk-in'); // walk-in, online, referral
            $table->unsignedBigInteger('converted_student_id')->nullable()->index();
            $table->foreign('school_id', 'ai_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_inquiries');
    }
};
