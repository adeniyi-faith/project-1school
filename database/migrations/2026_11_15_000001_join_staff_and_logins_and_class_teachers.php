<?php

use App\Models\School;
use App\Services\StaffAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('classes', 'class_teacher_staff_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->unsignedBigInteger('class_teacher_staff_id')->nullable()->index();
            });
        }
        if (! Schema::hasColumn('class_subjects', 'teacher_id')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
            });
        }

        // Join logins and staff that were made separately: match by email, and give every staff-role login a staff card
        $service = app(StaffAccountService::class);
        School::query()->orderBy('id')->each(function (School $school) use ($service) {
            $service->syncSchool($school->id);
        });

        // The old "class teacher" pointed at a login; carry it over to the matching staff card
        foreach (DB::table('classes')->whereNotNull('class_teacher_id')->whereNull('class_teacher_staff_id')->get(['id', 'school_id', 'class_teacher_id']) as $row) {
            $staffId = DB::table('staff')->where('school_id', $row->school_id)->where('user_id', $row->class_teacher_id)->value('id');
            if ($staffId) {
                DB::table('classes')->where('id', $row->id)->update(['class_teacher_staff_id' => $staffId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_subjects', 'teacher_id')) {
            Schema::table('class_subjects', fn (Blueprint $table) => $table->dropColumn('teacher_id'));
        }
        if (Schema::hasColumn('classes', 'class_teacher_staff_id')) {
            Schema::table('classes', fn (Blueprint $table) => $table->dropColumn('class_teacher_staff_id'));
        }
    }
};
