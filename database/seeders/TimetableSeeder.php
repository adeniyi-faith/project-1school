<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Database\Seeder;

class TimetableSeeder extends Seeder
{
    private array $slots = [
        ['start' => '07:30', 'end' => '08:15'],
        ['start' => '08:15', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '09:45'],
        ['start' => '09:45', 'end' => '10:30'],
        ['start' => '10:45', 'end' => '11:30'],
        ['start' => '11:30', 'end' => '12:15'],
    ];

    private array $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function run(): void
    {
        $school  = School::where('slug', 'greenfield-academy')->firstOrFail();
        $staff   = Staff::where('school_id', $school->id)->where('status', 'active')->get();
        $classes = SchoolClass::where('school_id', $school->id)->with('sections')->get();

        if ($staff->isEmpty() || $classes->isEmpty()) {
            $this->command->warn('Run StaffSeeder and SchoolSetupSeeder first.');
            return;
        }

        $count = 0;
        $rooms = ['101', '102', '103', '201', '202', '203', 'Lab A', 'Lab B'];

        foreach ($classes->take(3) as $class) {
            $section  = $class->sections->first();
            $subjects = Subject::where('class_id', $class->id)->get();

            if ($subjects->isEmpty()) continue;

            foreach ($this->days as $dayIdx => $day) {
                foreach ($this->slots as $slotIdx => $slot) {
                    $subject = $subjects[$slotIdx % $subjects->count()];
                    $teacher = $staff[$count % $staff->count()];

                    Timetable::firstOrCreate(
                        [
                            'school_id'   => $school->id,
                            'class_id'    => $class->id,
                            'section_id'  => $section?->id,
                            'day_of_week' => $day,
                            'start_time'  => $slot['start'],
                        ],
                        [
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'end_time'   => $slot['end'],
                            'room'       => $rooms[($dayIdx + $slotIdx) % count($rooms)],
                        ]
                    );
                    $count++;
                }
            }
        }

        $this->command->info("Seeded {$count} timetable periods for first 3 classes.");
    }
}
