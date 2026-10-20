<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Timetable;
use App\Services\StaffAccountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** A teacher's own home: the classes and subjects they are in charge of, and today's lessons */
class MyClassesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        // A login with a staff role always has a staff card; this makes one for older accounts the first time
        $staff = Staff::where('user_id', $user->id)->first()
            ?? (StaffAccountService::isStaffRole($user->roles->pluck('name')->first()) ? app(StaffAccountService::class)->staffFor($user) : null);

        $classes = [];
        $today = [];

        if ($staff) {
            $inCharge = SchoolClass::where('class_teacher_staff_id', $staff->id)->pluck('id');
            $lines = ClassSubject::with('subject:id,name')->where('teacher_id', $staff->id)->get();
            $timetable = Timetable::with(['subject:id,name', 'schoolClass:id,name', 'section:id,name'])->where('teacher_id', $staff->id)->orderBy('start_time')->get();

            $ids = $inCharge->merge($lines->pluck('class_id'))->merge($timetable->pluck('class_id'))->unique()->values();
            $counts = Student::where('status', 'active')->whereIn('class_id', $ids)->selectRaw('class_id, count(*) as n')->groupBy('class_id')->pluck('n', 'class_id');

            $classes = SchoolClass::whereIn('id', $ids)->orderBy('numeric_name')->orderBy('name')->get()->map(function (SchoolClass $c) use ($staff, $lines, $timetable, $counts) {
                $subjects = $lines->where('class_id', $c->id)->map(fn ($l) => $l->subject?->name)
                    ->merge($timetable->where('class_id', $c->id)->map(fn ($t) => $t->subject?->name))->filter()->unique()->sort()->values();

                return [
                    'id' => $c->id, 'name' => $c->name, 'students' => (int) ($counts[$c->id] ?? 0),
                    'class_teacher' => $c->class_teacher_staff_id === $staff->id, 'subjects' => $subjects,
                ];
            })->values();

            $day = strtolower(now()->format('l'));
            $today = $timetable->where('day_of_week', $day)->map(fn ($t) => [
                'id' => $t->id, 'start' => substr((string) $t->start_time, 0, 5), 'end' => substr((string) $t->end_time, 0, 5),
                'class' => trim(($t->schoolClass?->name ?? '').' '.($t->section?->name ?? '')), 'subject' => $t->subject?->name, 'room' => $t->room,
            ])->values();
        }

        return Inertia::render('SchoolAdmin/MyClasses/Index', [
            'managerLabel' => ClassController::managerLabel((int) $user->school_id),
            'staffName' => $staff?->full_name ?? $user->name,
            'hasStaffCard' => (bool) $staff,
            'classes' => $classes,
            'today' => $today,
        ]);
    }
}
