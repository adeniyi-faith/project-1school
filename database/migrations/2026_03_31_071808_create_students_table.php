<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('user_id')->nullable();       // linked user account
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->string('admission_no')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->enum('category', ['general', 'disabled', 'quota'])->default('general');
            $table->enum('status', ['active', 'alumni', 'transferred', 'inactive'])->default('active');
            $table->date('admission_date')->nullable();
            $table->string('previous_school')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id',   'students_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id',     'students_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('class_id',    'students_class_fk')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id',  'students_section_fk')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('guardian_id', 'students_guardian_fk')->references('id')->on('guardians')->nullOnDelete();
            $table->index('school_id', 'students_school_idx');
            $table->index(['school_id', 'class_id'], 'students_school_class_idx');
            $table->index(['school_id', 'status'],   'students_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
