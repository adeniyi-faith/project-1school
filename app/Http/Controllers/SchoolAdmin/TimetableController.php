<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    private array $days = ['monday','tuesday','wednesday','thursday','friday','saturday'];

    private array $defaultSlots = [
        ['start' => '07:30', 'end' => '08:15'],
        ['start' => '08:15', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '09:45'],
        ['start' => '09:45', 'end' => '10:30'],
        ['start' => '10:30', 'end' => '10:45'], // Break
        ['start' => '10:45', 'end' => '11:30'],
        ['start' => '11:30', 'end' => '12:15'],
        ['start' => '12:15', 'end' => '13:00'],
    ];

    public function index(Request $request): Response
    {
        $classId   = $request->class_id;
        $sectionId = $request->section_id;

        $periods = collect();

        if ($classId) {
            $periods = Timetable::with(['subject:id,name,code', 'teacher:id,first_name,last_name'])
                ->where('class_id', $classId)
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->get();
        }

        // Index by day+start_time for easy grid lookup
        $grid = [];
        foreach ($periods as $p) {
            $grid[$p->day_of_week][$p->start_time] = $p;
        }

        return Inertia::render('SchoolAdmin/Timetable/Index', [
            'classes'      => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'sections'     => Section::orderBy('name')->get(['id', 'class_id', 'name']),
            'subjects'     => $classId ? Subject::where('class_id', $classId)->orderBy('name')->get(['id', 'name', 'code']) : collect(),
            'teachers'     => Staff::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'periods'      => $periods,
            'grid'         => $grid,
            'days'         => $this->days,
            'defaultSlots' => $this->defaultSlots,
            'filters'      => ['class_id' => $classId, 'section_id' => $sectionId],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:staff,id',
            'day_of_week'=> 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'room'       => 'nullable|string|max:50',
            'notes'      => 'nullable|string|max:200',
        ]);

        // Teacher conflict check
        if (!empty($data['teacher_id'])) {
            $conflict = Timetable::where('school_id', $this->getSchoolId())
                ->where('teacher_id', $data['teacher_id'])
                ->where('day_of_week', $data['day_of_week'])
                ->where('start_time', $data['start_time'])
                ->exists();

            if ($conflict) {
                return back()->withErrors(['teacher_id' => 'This teacher already has a class at this time.']);
            }
        }

        Timetable::updateOrCreate(
            [
                'school_id'  => $this->getSchoolId(),
                'class_id'   => $data['class_id'],
                'section_id' => $data['section_id'] ?? null,
                'day_of_week'=> $data['day_of_week'],
                'start_time' => $data['start_time'],
            ],
            array_merge($data, ['school_id' => $this->getSchoolId()])
        );

        return back()->with('success', 'Period saved.');
    }

    public function destroy(Timetable $timetable): RedirectResponse
    {
        $timetable->delete();
        return back()->with('success', 'Period removed.');
    }

    /**
     * Teacher's personal weekly schedule.
     */
    public function teacherSchedule(Request $request): Response
    {
        $teacherId = $request->teacher_id;

        $periods = collect();
        if ($teacherId) {
            $periods = Timetable::with(['schoolClass:id,name', 'section:id,name', 'subject:id,name'])
                ->where('teacher_id', $teacherId)
                ->get();
        }

        $grid = [];
        foreach ($periods as $p) {
            $grid[$p->day_of_week][$p->start_time] = $p;
        }

        return Inertia::render('SchoolAdmin/Timetable/TeacherSchedule', [
            'teachers'     => Staff::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'emp_id']),
            'periods'      => $periods,
            'grid'         => $grid,
            'days'         => $this->days,
            'defaultSlots' => $this->defaultSlots,
            'filters'      => ['teacher_id' => $teacherId],
        ]);
    }
}
