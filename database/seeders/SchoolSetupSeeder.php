<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Shift;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSetupSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        // ── Classes ───────────────────────────────────────────────────
        $classData = [
            ['name' => 'Class 1',  'numeric_name' => 1,  'capacity' => 40],
            ['name' => 'Class 2',  'numeric_name' => 2,  'capacity' => 40],
            ['name' => 'Class 3',  'numeric_name' => 3,  'capacity' => 40],
            ['name' => 'Class 4',  'numeric_name' => 4,  'capacity' => 42],
            ['name' => 'Class 5',  'numeric_name' => 5,  'capacity' => 42],
            ['name' => 'Class 6',  'numeric_name' => 6,  'capacity' => 45],
            ['name' => 'Class 7',  'numeric_name' => 7,  'capacity' => 45],
            ['name' => 'Class 8',  'numeric_name' => 8,  'capacity' => 45],
            ['name' => 'Class 9',  'numeric_name' => 9,  'capacity' => 50],
            ['name' => 'Class 10', 'numeric_name' => 10, 'capacity' => 50],
        ];

        $classes = [];
        foreach ($classData as $row) {
            $classes[$row['numeric_name']] = SchoolClass::firstOrCreate(
                ['school_id' => $sid, 'name' => $row['name']],
                ['numeric_name' => $row['numeric_name'], 'capacity' => $row['capacity']]
            );
        }

        // ── Sections ──────────────────────────────────────────────────
        $sectionNames = ['A', 'B', 'C'];
        foreach ($classes as $num => $class) {
            // Class 9 & 10 get Science / Arts / Commerce instead
            $names = $num >= 9 ? ['Science', 'Arts', 'Commerce'] : $sectionNames;
            foreach ($names as $name) {
                Section::firstOrCreate(
                    ['school_id' => $sid, 'class_id' => $class->id, 'name' => $name],
                    ['capacity' => (int) ($class->capacity / count($names))]
                );
            }
        }

        // ── Subjects (per class group) ────────────────────────────────
        $primarySubjects = [
            ['name' => 'Bangla',        'code' => 'BAN', 'type' => 'theory'],
            ['name' => 'English',        'code' => 'ENG', 'type' => 'theory'],
            ['name' => 'Mathematics',    'code' => 'MAT', 'type' => 'theory'],
            ['name' => 'Science',        'code' => 'SCI', 'type' => 'theory'],
            ['name' => 'Social Studies', 'code' => 'SST', 'type' => 'theory'],
            ['name' => 'Religion',       'code' => 'REL', 'type' => 'theory'],
            ['name' => 'Arts & Crafts',  'code' => 'ART', 'type' => 'practical'],
        ];

        $secondarySubjects = [
            ['name' => 'Bangla',         'code' => 'BAN', 'type' => 'theory'],
            ['name' => 'English',         'code' => 'ENG', 'type' => 'theory'],
            ['name' => 'Mathematics',     'code' => 'MAT', 'type' => 'theory'],
            ['name' => 'Physics',         'code' => 'PHY', 'type' => 'theory'],
            ['name' => 'Chemistry',       'code' => 'CHE', 'type' => 'theory'],
            ['name' => 'Biology',         'code' => 'BIO', 'type' => 'theory'],
            ['name' => 'ICT',             'code' => 'ICT', 'type' => 'practical'],
            ['name' => 'Physical Education', 'code' => 'PE', 'type' => 'practical'],
        ];

        foreach ($classes as $num => $class) {
            $subjects = $num <= 5 ? $primarySubjects : $secondarySubjects;
            foreach ($subjects as $sub) {
                Subject::firstOrCreate(
                    ['school_id' => $sid, 'class_id' => $class->id, 'name' => $sub['name']],
                    ['code' => $sub['code'], 'type' => $sub['type'], 'full_marks' => 100, 'pass_marks' => 33]
                );
            }
        }

        // ── Shifts ────────────────────────────────────────────────────
        $shifts = [
            ['name' => 'Morning Shift', 'start_time' => '07:30', 'end_time' => '12:30'],
            ['name' => 'Day Shift',     'start_time' => '08:00', 'end_time' => '14:00'],
            ['name' => 'Evening Shift', 'start_time' => '14:00', 'end_time' => '19:00'],
        ];
        foreach ($shifts as $shift) {
            Shift::firstOrCreate(['school_id' => $sid, 'name' => $shift['name']], $shift);
        }

        // ── Holidays ──────────────────────────────────────────────────
        $holidays = [
            ['name' => 'New Year\'s Day',         'date' => '2026-01-01', 'description' => 'National holiday'],
            ['name' => 'International Mother Language Day', 'date' => '2026-02-21', 'description' => 'National holiday'],
            ['name' => 'Independence Day',         'date' => '2026-03-26', 'description' => 'National holiday'],
            ['name' => 'Eid-ul-Fitr (Day 1)',      'date' => '2026-03-30', 'description' => 'Religious holiday'],
            ['name' => 'Eid-ul-Fitr (Day 2)',      'date' => '2026-03-31', 'description' => 'Religious holiday'],
            ['name' => 'Eid-ul-Fitr (Day 3)',      'date' => '2026-04-01', 'description' => 'Religious holiday'],
            ['name' => 'Bengali New Year',         'date' => '2026-04-14', 'description' => 'Pahela Boishakh'],
            ['name' => 'May Day',                  'date' => '2026-05-01', 'description' => 'International Labour Day'],
            ['name' => 'Eid-ul-Adha (Day 1)',      'date' => '2026-06-07', 'description' => 'Religious holiday'],
            ['name' => 'Eid-ul-Adha (Day 2)',      'date' => '2026-06-08', 'description' => 'Religious holiday'],
            ['name' => 'National Mourning Day',    'date' => '2026-08-15', 'description' => 'National holiday'],
            ['name' => 'Victory Day',              'date' => '2026-12-16', 'description' => 'National holiday'],
            ['name' => 'Christmas Day',            'date' => '2026-12-25', 'description' => 'Religious holiday'],
        ];
        foreach ($holidays as $h) {
            Holiday::firstOrCreate(['school_id' => $sid, 'date' => $h['date']], $h);
        }

        $this->command->info('School setup seeded: 10 classes, sections, subjects, 3 shifts, 13 holidays.');
    }
}
