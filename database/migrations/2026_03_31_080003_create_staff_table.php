<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('designation_id')->nullable()->index();
            $table->string('emp_id', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('nationality', 50)->nullable()->default('Bangladeshi');
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('salary_type', ['fixed', 'hourly'])->default('fixed');
            $table->decimal('salary', 12, 2)->nullable();
            $table->enum('status', ['active', 'resigned', 'terminated', 'on_leave'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id',     'staff_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id',       'staff_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id', 'staff_department_fk')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('designation_id','staff_designation_fk')->references('id')->on('designations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
