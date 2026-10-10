<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudentSeeder extends Seeder
{
    private array $firstNames = [
        'Rahim', 'Karim', 'Jamal', 'Farhan', 'Imran', 'Sabbir', 'Rakib', 'Nasir', 'Tanvir', 'Sohel',
        'Fatema', 'Nusrat', 'Sadia', 'Tania', 'Mitu', 'Riya', 'Priya', 'Suma', 'Lima', 'Shirin',
        'Anik', 'Badhon', 'Dipu', 'Emon', 'Fuad', 'Golam', 'Habib', 'Irfan', 'Jewel', 'Kabir',
        'Layla', 'Mina', 'Nadia', 'Orin', 'Puja', 'Ruma', 'Sonia', 'Tina', 'Uma', 'Vina',
    ];

    private array $lastNames = [
        'Hossain', 'Islam', 'Ahmed', 'Khan', 'Rahman', 'Begum', 'Akter', 'Miah', 'Uddin', 'Sarkar',
        'Chowdhury', 'Sheikh', 'Mondal', 'Das', 'Roy', 'Paul', 'Biswas', 'Ghosh', 'Dey', 'Bose',
    ];

    private array $guardianFirstNames = [
        'Mohammad', 'Abdul', 'Md.', 'Abul', 'Habibur', 'Nurul', 'Jahangir', 'Rafiqul', 'Sirajul', 'Mahabub',
        'Nasima', 'Rehana', 'Kulsum', 'Halima', 'Rashida', 'Amena', 'Sufia', 'Bilkis', 'Morjina', 'Shahnaz',
    ];

    private array $religions    = ['Islam', 'Hinduism', 'Christianity', 'Buddhism'];
    private array $bloodGroups  = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    private array $relations    = ['Father', 'Mother', 'Guardian'];
    private array $occupations  = ['Businessman', 'Farmer', 'Teacher', 'Driver', 'Engineer', 'Doctor', 'Garments Worker', 'Government Employee'];
    private array $areas        = ['Dhaka', 'Mirpur', 'Uttara', 'Motijheel', 'Mohammadpur', 'Dhanmondi', 'Gulshan', 'Banani'];

    public function run(): void
    {
        $school  = School::where('slug', 'greenfield-academy')->firstOrFail();
        $classes = SchoolClass::where('school_id', $school->id)->with('sections')->get();

        if ($classes->isEmpty()) {
            $this->command->warn('Run SchoolSetupSeeder first.');
            return;
        }

        $count = 0;

        foreach ($classes as $class) {
            $sections = $class->sections;
            if ($sections->isEmpty()) continue;

            // 8 students per class (spread across sections)
            for ($i = 0; $i < 8; $i++) {
                $section  = $sections[$i % $sections->count()];
                $gender   = $i % 2 === 0 ? 'male' : 'female';
                $dob      = Carbon::now()->subYears(rand(8, 17))->subDays(rand(0, 364));
                $admitted = Carbon::now()->subMonths(rand(1, 24));

                $firstName = $this->firstNames[array_rand($this->firstNames)];
                $lastName  = $this->lastNames[array_rand($this->lastNames)];

                $guardian = Guardian::create([
                    'school_id'  => $school->id,
                    'name'       => $this->guardianFirstNames[array_rand($this->guardianFirstNames)] . ' ' . $lastName,
                    'relation'   => $this->relations[array_rand($this->relations)],
                    'phone'      => '017' . rand(10000000, 99999999),
                    'email'      => strtolower($lastName) . rand(10, 99) . '@gmail.com',
                    'occupation' => $this->occupations[array_rand($this->occupations)],
                    'address'    => rand(1, 99) . ' Road ' . rand(1, 20) . ', ' . $this->areas[array_rand($this->areas)],
                ]);

                $year  = $admitted->format('Y');
                $total = Student::withoutGlobalScopes()->where('school_id', $school->id)->count() + 1;
                $admNo = "ADM-{$year}-" . str_pad($total, 4, '0', STR_PAD_LEFT);

                Student::create([
                    'school_id'      => $school->id,
                    'class_id'       => $class->id,
                    'section_id'     => $section->id,
                    'guardian_id'    => $guardian->id,
                    'admission_no'   => $admNo,
                    'roll_no'        => str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'gender'         => $gender,
                    'date_of_birth'  => $dob->toDateString(),
                    'blood_group'    => $this->bloodGroups[array_rand($this->bloodGroups)],
                    'religion'       => $this->religions[array_rand($this->religions)],
                    'nationality'    => 'Bangladeshi',
                    'phone'          => rand(0, 1) ? '018' . rand(10000000, 99999999) : null,
                    'address'        => rand(1, 99) . ' Lane ' . rand(1, 10) . ', ' . $this->areas[array_rand($this->areas)],
                    'category'       => 'general',
                    'status'         => $i < 7 ? 'active' : 'alumni',
                    'admission_date' => $admitted->toDateString(),
                ]);

                $count++;
            }
        }

        $this->command->info("Seeded {$count} students across {$classes->count()} classes.");
    }
}
