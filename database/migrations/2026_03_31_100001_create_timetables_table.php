<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('class_id')->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();  // FK to staff
            $table->enum('day_of_week', ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Conflict: same class/section cannot have two subjects at the same time/day
            $table->unique(
                ['school_id', 'class_id', 'section_id', 'day_of_week', 'start_time'],
                'timetable_class_slot_unique'
            );

            $table->foreign('school_id',  'tt_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id',   'tt_class_fk')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id', 'tt_section_fk')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('subject_id', 'tt_subject_fk')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id', 'tt_teacher_fk')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
