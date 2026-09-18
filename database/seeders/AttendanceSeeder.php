<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $school   = School::where('slug', 'greenfield-academy')->firstOrFail();
        $students = Student::where('school_id', $school->id)->where('status', 'active')->get();
        $staff    = Staff::where('school_id', $school->id)->where('status', 'active')->get();

        if ($students->isEmpty() && $staff->isEmpty()) {
            $this->command->warn('No students or staff found. Run StudentSeeder and StaffSeeder first.');
            return;
        }

        $statuses = ['present', 'present', 'present', 'present', 'present', 'absent', 'late', 'half_day'];
        $count    = 0;

        // Seed last 30 weekdays
        $date = Carbon::now()->startOfDay();
        $days = 0;

        while ($days < 30) {
            // Skip weekends (Friday=5, Saturday=6 in Bangladesh)
            if (in_array($date->dayOfWeek, [5, 6])) {
                $date->subDay();
                continue;
            }

            $dateStr = $date->toDateString();

            // Student attendance
            foreach ($students as $student) {
                Attendance::firstOrCreate(
                    [
                        'school_id'       => $school->id,
                        'date'            => $dateStr,
                        'attendable_type' => Student::class,
                        'attendable_id'   => $student->id,
                    ],
                    [
                        'status'  => $statuses[array_rand($statuses)],
                        'remarks' => null,
                    ]
                );
                $count++;
            }

            // Staff attendance
            foreach ($staff as $member) {
                Attendance::firstOrCreate(
                    [
                        'school_id'       => $school->id,
                        'date'            => $dateStr,
                        'attendable_type' => Staff::class,
                        'attendable_id'   => $member->id,
                    ],
                    [
                        'status'  => $statuses[array_rand($statuses)],
                        'remarks' => null,
                    ]
                );
                $count++;
            }

            $date->subDay();
            $days++;
        }

        $this->command->info("Seeded {$count} attendance records for last 30 weekdays.");
    }
}
