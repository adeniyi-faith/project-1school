<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Homework;
use App\Models\Student;
use App\Models\Timetable;
use Carbon\Carbon;
use Inertia\Inertia;

/** Read-only homework and timetable for each of a parent's children, the same things their child sees. */
class ParentClassworkController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function index()
    {
        $user     = auth()->user();
        $guardian = Guardian::with('students.schoolClass:id,name', 'students.section:id,name')
            ->where('school_id', $user->school_id)->where('user_id', $user->id)->first();

        if (! $guardian) {
            return Inertia::render('Parent/Classwork', ['linked' => false, 'children' => [], 'today' => null]);
        }

        $today    = Carbon::now()->toDateString();
        // Homework from the last two weeks onwards: older overdue work is noise
        $earliest = Carbon::now()->subDays(14)->toDateString();

        $children = $guardian->students->map(function (Student $student) use ($today, $earliest) {
            $homework = Homework::where('school_id', $student->school_id)
                ->where('class_id', $student->class_id)
                ->where('is_active', true)
                ->whereDate('due_date', '>=', $earliest)
                ->with('subject:id,name')
                ->orderBy('due_date')
                ->get()
                ->map(fn ($h) => [
                    'id'          => $h->id,
                    'title'       => $h->title,
                    'description' => $h->description,
                    'subject'     => $h->subject?->name,
                    'due_label'   => Carbon::parse($h->due_date)->format('D d M Y'),
                    'overdue'     => Carbon::parse($h->due_date)->toDateString() < $today,
                ])->values();

            $timetable = [];
            foreach (self::DAYS as $day) {
                $slots = Timetable::where('school_id', $student->school_id)
                    ->where('class_id', $student->class_id)
                    ->when($student->section_id, fn ($q, $sid) => $q->where('section_id', $sid), fn ($q) => $q->whereNull('section_id'))
                    ->where('day_of_week', $day)
                    ->with('subject:id,name')
                    ->orderBy('start_time')
                    ->get()
                    ->map(fn ($t) => [
                        'subject'    => $t->subject?->name,
                        'start_time' => substr($t->start_time, 0, 5),
                        'end_time'   => substr($t->end_time, 0, 5),
                        'room'       => $t->room,
                    ])->values();
                if ($slots->isNotEmpty()) {
                    $timetable[$day] = $slots;
                }
            }

            return [
                'id'        => $student->id,
                'full_name' => $student->full_name,
                'class'     => $student->schoolClass?->name,
                'section'   => $student->section?->name,
                'homework'  => $homework,
                'timetable' => $timetable,
            ];
        })->values();

        return Inertia::render('Parent/Classwork', [
            'linked'   => true,
            'children' => $children,
            'today'    => strtolower(Carbon::now()->format('l')),
        ]);
    }
}
