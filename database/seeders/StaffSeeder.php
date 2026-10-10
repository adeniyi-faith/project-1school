<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\School;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StaffSeeder extends Seeder
{
    private array $firstNames = [
        'Kamrul', 'Rafiqul', 'Shahidul', 'Mizanur', 'Mahbubur', 'Anisur', 'Hasanur', 'Golam',
        'Nasrin', 'Roksana', 'Shahnaz', 'Fatema', 'Morjina', 'Bilkis', 'Kulsum', 'Rehana',
        'Aminul', 'Shafiqul', 'Belal', 'Moniruzzaman',
    ];

    private array $lastNames = [
        'Hossain', 'Islam', 'Ahmed', 'Khan', 'Rahman', 'Begum', 'Akter', 'Sarkar', 'Chowdhury', 'Sheikh',
    ];

    private array $religions   = ['Islam', 'Hinduism', 'Christianity', 'Buddhism'];
    private array $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    private array $areas       = ['Dhaka', 'Mirpur', 'Uttara', 'Motijheel', 'Mohammadpur', 'Gulshan'];

    private array $departmentData = [
        ['name' => 'Science & Mathematics', 'code' => 'SCI'],
        ['name' => 'Languages & Humanities', 'code' => 'LNG'],
        ['name' => 'Social Studies',         'code' => 'SST'],
        ['name' => 'Arts & Physical Education', 'code' => 'APE'],
        ['name' => 'Administration',         'code' => 'ADM'],
    ];

    private array $designationData = [
        'SCI' => ['Head of Department', 'Senior Teacher', 'Junior Teacher', 'Lab Assistant'],
        'LNG' => ['Head of Department', 'Senior Teacher', 'Junior Teacher'],
        'SST' => ['Head of Department', 'Senior Teacher', 'Junior Teacher'],
        'APE' => ['Head of Department', 'PE Instructor', 'Arts Teacher'],
        'ADM' => ['Principal', 'Vice Principal', 'Accountant', 'Office Assistant', 'Librarian'],
    ];

    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        // ── Departments & Designations ────────────────────────────────
        $departments = [];
        foreach ($this->departmentData as $row) {
            $dept = Department::firstOrCreate(
                ['school_id' => $sid, 'name' => $row['name']],
                ['code' => $row['code']],
            );
            $departments[$row['code']] = $dept;

            foreach ($this->designationData[$row['code']] as $desigName) {
                Designation::firstOrCreate(
                    ['school_id' => $sid, 'department_id' => $dept->id, 'name' => $desigName],
                );
            }
        }

        // ── Staff ─────────────────────────────────────────────────────
        $count = 0;
        foreach ($departments as $code => $dept) {
            $designations = Designation::where('department_id', $dept->id)->get();
            $staffPerDept = $code === 'ADM' ? 4 : 5;

            for ($i = 0; $i < $staffPerDept; $i++) {
                $firstName = $this->firstNames[array_rand($this->firstNames)];
                $lastName  = $this->lastNames[array_rand($this->lastNames)];
                $gender    = ($i % 2 === 0) ? 'male' : 'female';
                $desig     = $designations[$i % $designations->count()];
                $joiningDate = Carbon::now()->subMonths(rand(3, 36));

                Staff::create([
                    'school_id'      => $sid,
                    'department_id'  => $dept->id,
                    'designation_id' => $desig->id,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'gender'         => $gender,
                    'date_of_birth'  => Carbon::now()->subYears(rand(25, 55))->subDays(rand(0, 364))->toDateString(),
                    'blood_group'    => $this->bloodGroups[array_rand($this->bloodGroups)],
                    'religion'       => $this->religions[array_rand($this->religions)],
                    'nationality'    => 'Bangladeshi',
                    'phone'          => '017' . rand(10000000, 99999999),
                    'email'          => strtolower($firstName) . rand(10, 99) . '@school.edu.bd',
                    'address'        => rand(1, 99) . ' Road ' . rand(1, 20) . ', ' . $this->areas[array_rand($this->areas)],
                    'joining_date'   => $joiningDate->toDateString(),
                    'salary_type'    => 'fixed',
                    'salary'         => rand(15, 60) * 1000,
                    'status'         => $i < $staffPerDept - 1 ? 'active' : ($i % 4 === 0 ? 'on_leave' : 'active'),
                ]);

                $count++;
            }
        }

        $this->command->info("Seeded {$count} staff members across " . count($departments) . ' departments.');
    }
}
