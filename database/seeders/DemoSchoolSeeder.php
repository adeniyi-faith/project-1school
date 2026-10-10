<?php

namespace Database\Seeders;

use App\Models\ResultSheet;
use App\Services\TermResultService;
use App\Support\DemoSchool;
use App\Support\SchoolDefaults;
use Database\Seeders\Demo\NigerianNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills the public demo school with a believable Nigerian private school:
 * Creche to SS3, around 370 students with families, staff, fees in naira,
 * attendance, exam results, a timetable, homework, announcements and a
 * library. It only ever touches the demo school, so it is safe to run again
 * at any time to refresh the demo.
 *
 *   php artisan db:seed --class=DemoSchoolSeeder --force
 */
class DemoSchoolSeeder extends Seeder
{
    private int $sid;
    private Carbon $now;
    private int $adminUserId;

    /** @var array<string,array> */
    private array $groups;

    /** families already created, so siblings can share parents */
    private array $families = [];

    private array $admissionCounters = [];

    public function run(): void
    {
        mt_srand(20261010); // same demo every time

        ['school' => $school, 'user' => $admin] = DemoSchool::ensure();
        $this->sid = $school->id;
        $this->adminUserId = $admin->id;
        $this->now = Carbon::now();
        $this->groups = NigerianNames::groups();

        DB::transaction(function () use ($school) {
            $this->wipe();
            $this->profile($school);
            $this->academicYears();
            // Terms for each year, the CA1 + CA2 + Exam setup and the WAEC grade scale
            SchoolDefaults::apply($this->sid);
            $classes = $this->classes();
            $subjects = $this->subjects($classes);
            $teachers = $this->staff();
            $students = $this->students($classes);
            $this->holidays();
            $scales = $this->gradeScales($classes);
            $this->exams($classes, $subjects, $students, $scales);
            $this->termResults($classes);
            $this->fees($classes, $students);
            $this->attendance($students, $teachers);
            $this->timetable($classes, $subjects, $teachers);
            $this->homework($classes, $subjects, $teachers);
            $this->announcements($classes);
            $this->library();
        });

        $this->command?->info('Demo school filled: Heritage Crown Academy, Lagos.');
    }

    // ───────────────────────── helpers ─────────────────────────

    private function pick(array $list): mixed
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    private function chance(float $p): bool
    {
        return mt_rand() / mt_getrandmax() < $p;
    }

    private function weighted(array $weights): string
    {
        $total = array_sum($weights);
        $r = mt_rand(1, (int) round($total * 100)) / 100;
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return (string) $key;
            }
        }

        return (string) array_key_last($weights);
    }

    private function gauss(float $mean, float $sd): float
    {
        $u = max(mt_rand() / mt_getrandmax(), 1e-9);
        $v = mt_rand() / mt_getrandmax();

        return $mean + $sd * sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }

    private function insert(string $table, array $rows): void
    {
        $stamp = $this->now->toDateTimeString();
        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table($table)->insert(array_map(
                fn (array $r) => $r + ['created_at' => $stamp, 'updated_at' => $stamp],
                $chunk,
            ));
        }
    }

    /** Insert one row and return its new id */
    private function insertGetId(string $table, array $row): int
    {
        $stamp = $this->now->toDateTimeString();

        return (int) DB::table($table)->insertGetId($row + ['created_at' => $stamp, 'updated_at' => $stamp]);
    }

    private function phone(): string
    {
        return $this->pick(['0803', '0805', '0806', '0807', '0810', '0813', '0814', '0816', '0703', '0706', '0802', '0808', '0812', '0902', '0909', '0701'])
            . mt_rand(1000000, 9999999);
    }

    private function address(): string
    {
        [$area, $streets] = $this->pick(NigerianNames::lagosAreas());

        return mt_rand(1, 48) . ', ' . $this->pick($streets) . ', ' . $area . ', Lagos';
    }

    private function bloodGroup(): string
    {
        return $this->weighted(['O+' => 47, 'A+' => 22, 'B+' => 18, 'O-' => 4, 'AB+' => 4, 'A-' => 2, 'B-' => 2, 'AB-' => 1]);
    }

    // ───────────────────────── wipe ─────────────────────────

    private function wipe(): void
    {
        $sid = $this->sid;
        $studentIds = DB::table('students')->where('school_id', $sid)->pluck('id');
        $staffIds = DB::table('staff')->where('school_id', $sid)->pluck('id');
        $bookIds = DB::table('books')->where('school_id', $sid)->pluck('id');
        $homeworkIds = DB::table('homework')->where('school_id', $sid)->pluck('id');

        $byIds = function (string $table, string $column, $ids) {
            if ($ids->isNotEmpty() && Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                foreach ($ids->chunk(500) as $chunk) {
                    DB::table($table)->whereIn($column, $chunk)->delete();
                }
            }
        };

        $byIds('homework_submissions', 'homework_id', $homeworkIds);
        $byIds('student_route', 'student_id', $studentIds);
        $byIds('student_documents', 'student_id', $studentIds);
        $byIds('hostel_allocations', 'student_id', $studentIds);
        $byIds('book_issues', 'book_id', $bookIds);
        $byIds('book_reservations', 'book_id', $bookIds);
        $byIds('leave_requests', 'staff_id', $staffIds);
        $byIds('payrolls', 'staff_id', $staffIds);
        $byIds('salary_structures', 'staff_id', $staffIds);
        $byIds('staff_documents', 'staff_id', $staffIds);

        foreach ([
            'behaviour_ratings', 'term_result_summaries', 'term_results', 'subject_scores', 'result_sheets', 'behaviour_traits',
            'marks', 'exams', 'ledger_entries', 'invoices', 'student_scholarships', 'scholarships', 'fee_payments', 'fee_structures', 'fee_categories', 'attendances',
            'timetables', 'homework', 'books', 'announcements', 'holidays', 'grade_scales',
            'students', 'guardians', 'staff', 'designations', 'departments', 'sections',
            'subjects', 'classes', 'grading_schemes', 'assessment_components', 'assessment_schemes',
            'terms', 'academic_years',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                DB::table($table)->where('school_id', $sid)->delete();
            }
        }
    }

    // ───────────────────────── school profile & years ─────────────────────────

    private function profile($school): void
    {
        $school->forceFill([
            'name' => 'Heritage Crown Academy',
            'email' => 'info@heritagecrown.edu.ng',
            'phone' => '0803 555 0142',
            'address' => '27, Admiralty Way, Lekki Phase 1',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'NG',
            'timezone' => 'Africa/Lagos',
            'currency' => 'NGN',
            'language' => 'en',
            'status' => 'active',
        ])->save();
    }

    private function academicYears(): void
    {
        $this->insert('academic_years', [
            ['school_id' => $this->sid, 'name' => '2025/2026', 'start_date' => '2025-09-08', 'end_date' => '2026-07-24', 'is_current' => false],
            ['school_id' => $this->sid, 'name' => '2026/2027', 'start_date' => '2026-09-07', 'end_date' => '2027-07-23', 'is_current' => true],
        ]);
    }

    // ───────────────────────── classes, sections, subjects ─────────────────────────

    private function classes(): array
    {
        $defs = [
            ['Creche', 'early', 2, 14, ['A'], 20],
            ['Nursery 1', 'early', 3, 18, ['A', 'B'], 30],
            ['Nursery 2', 'early', 4, 20, ['A', 'B'], 30],
            ['Nursery 3', 'early', 5, 22, ['A', 'B'], 30],
            ['Primary 1', 'primary', 6, 24, ['A', 'B'], 36],
            ['Primary 2', 'primary', 7, 25, ['A', 'B'], 36],
            ['Primary 3', 'primary', 8, 25, ['A', 'B'], 36],
            ['Primary 4', 'primary', 9, 26, ['A', 'B'], 36],
            ['Primary 5', 'primary', 10, 26, ['A', 'B'], 36],
            ['Primary 6', 'primary', 11, 24, ['A', 'B'], 36],
            ['JSS 1', 'junior', 12, 28, ['A', 'B'], 45],
            ['JSS 2', 'junior', 13, 27, ['A', 'B'], 45],
            ['JSS 3', 'junior', 14, 26, ['A', 'B'], 45],
            ['SS 1', 'senior', 15, 24, ['Science', 'Arts', 'Commercial'], 45],
            ['SS 2', 'senior', 16, 23, ['Science', 'Arts', 'Commercial'], 45],
            ['SS 3', 'senior', 17, 22, ['Science', 'Arts', 'Commercial'], 45],
        ];

        $classes = [];
        foreach ($defs as $i => [$name, $level, $age, $count, $sections, $capacity]) {
            $id = $this->insertGetId('classes', [
                'school_id' => $this->sid, 'name' => $name, 'numeric_name' => $i + 1, 'capacity' => $capacity,
            ]);
            $sectionIds = [];
            foreach ($sections as $s) {
                $sectionIds[$s] = $this->insertGetId('sections', [
                    'school_id' => $this->sid, 'class_id' => $id, 'name' => $s,
                    'capacity' => (int) ceil($capacity / count($sections)),
                ]);
            }
            $classes[] = ['id' => $id, 'name' => $name, 'level' => $level, 'age' => $age, 'count' => $count, 'sections' => $sectionIds, 'index' => $i];
        }

        return $classes;
    }

    private function subjectCatalog(): array
    {
        return [
            'early' => [
                ['Number Work', 'NUM'], ['Letter Work & Phonics', 'PHO'], ['Rhymes & Songs', 'RHY'], ['Health Habits', 'HEA'],
                ['Social Habits', 'SOC'], ['Creative Arts & Colouring', 'ART', 'practical'], ['Bible Stories / Islamic Studies', 'REL'], ['Basic Computer Skills', 'ICT', 'practical'],
            ],
            'primary' => [
                ['English Studies', 'ENG'], ['Mathematics', 'MTH'], ['Basic Science & Technology', 'BST'], ['Social Studies', 'SST'],
                ['Civic Education', 'CIV'], ['Verbal Reasoning', 'VRB'], ['Quantitative Reasoning', 'QNT'], ['Religious Studies (CRS/IRS)', 'REL'],
                ['Computer Studies', 'ICT', 'practical'], ['Yoruba Language', 'YOR'], ['Cultural & Creative Arts', 'CCA', 'practical'], ['Physical & Health Education', 'PHE', 'practical'],
                ['Agricultural Science', 'AGR'], ['Home Economics', 'HEC', 'practical'],
            ],
            'junior' => [
                ['English Language', 'ENG'], ['Mathematics', 'MTH'], ['Basic Science', 'BSC'], ['Basic Technology', 'BTE', 'practical'],
                ['Social Studies', 'SST'], ['Civic Education', 'CIV'], ['Business Studies', 'BUS'], ['Religious Studies (CRS/IRS)', 'REL'],
                ['Computer Studies', 'ICT', 'practical'], ['Yoruba Language', 'YOR'], ['French', 'FRE'], ['Agricultural Science', 'AGR'],
                ['Home Economics', 'HEC', 'practical'], ['Cultural & Creative Arts', 'CCA', 'practical'], ['Physical & Health Education', 'PHE', 'practical'],
            ],
            // third item = type, fourth = which SS tracks take it (null = everyone)
            'senior' => [
                ['English Language', 'ENG', 'theory', null], ['Mathematics', 'MTH', 'theory', null], ['Civic Education', 'CIV', 'theory', null], ['Computer Studies (ICT)', 'ICT', 'practical', null],
                ['Physics', 'PHY', 'theory', ['Science']], ['Chemistry', 'CHM', 'theory', ['Science']], ['Biology', 'BIO', 'theory', ['Science']],
                ['Further Mathematics', 'FMT', 'theory', ['Science']], ['Agricultural Science', 'AGR', 'theory', ['Science']],
                ['Literature-in-English', 'LIT', 'theory', ['Arts']], ['Christian Religious Studies', 'CRS', 'theory', ['Arts']], ['History', 'HIS', 'theory', ['Arts']],
                ['Fine Art', 'FIN', 'practical', ['Arts']], ['Government', 'GOV', 'theory', ['Arts', 'Commercial']],
                ['Economics', 'ECO', 'theory', ['Commercial']], ['Financial Accounting', 'ACC', 'theory', ['Commercial']], ['Commerce', 'COM', 'theory', ['Commercial']], ['Marketing', 'MKT', 'theory', ['Commercial']],
            ],
        ];
    }

    /** @return array<int,array> subjects keyed by class id */
    private function subjects(array $classes): array
    {
        $catalog = $this->subjectCatalog();
        $out = [];

        foreach ($classes as $class) {
            $rows = [];
            foreach ($catalog[$class['level']] as $s) {
                $id = $this->insertGetId('subjects', [
                    'school_id' => $this->sid, 'class_id' => $class['id'], 'name' => $s[0], 'code' => $s[1],
                    'type' => $s[2] ?? 'theory', 'full_marks' => 100, 'pass_marks' => 40,
                ]);
                $rows[$id] = ['id' => $id, 'name' => $s[0], 'tracks' => $s[3] ?? null];
            }
            $out[$class['id']] = $rows;
        }

        return $out;
    }

    /** Subjects one student takes (SS students take core plus their own track) */
    private function subjectsFor(array $classSubjects, string $sectionName, string $level): array
    {
        if ($level !== 'senior') {
            return $classSubjects;
        }

        return array_filter($classSubjects, fn ($s) => $s['tracks'] === null || in_array($sectionName, $s['tracks'], true));
    }

    // ───────────────────────── staff ─────────────────────────

    private function staff(): array
    {
        $depts = [];
        foreach ([
            ['Administration', 'ADM'], ['Early Years', 'EYR'], ['Primary School', 'PRI'], ['Junior Secondary', 'JSS'],
            ['Senior Secondary', 'SSS'], ['Finance & Bursary', 'FIN'], ['Support Services', 'SUP'],
        ] as [$name, $code]) {
            $depts[$code] = $this->insertGetId('departments', ['school_id' => $this->sid, 'name' => $name, 'code' => $code]);
        }

        $desigIds = [];
        $desig = function (string $name, string $dept) use (&$desigIds, $depts) {
            return $desigIds[$name] ??= $this->insertGetId('designations', [
                'school_id' => $this->sid, 'department_id' => $depts[$dept], 'name' => $name,
            ]);
        };

        $early = ['Number Work', 'Letter Work & Phonics', 'Rhymes & Songs', 'Health Habits', 'Social Habits', 'Creative Arts & Colouring', 'Bible Stories / Islamic Studies', 'Basic Computer Skills'];
        $primaryCore = ['English Studies', 'Mathematics', 'Basic Science & Technology', 'Social Studies', 'Civic Education', 'Verbal Reasoning', 'Quantitative Reasoning'];

        // [role, department, level, specialties, count, salary range, gender hint]
        $plan = [
            ['Principal', 'ADM', null, [], 1, [650000, 650000], 'any'],
            ['Vice Principal (Academics)', 'ADM', null, [], 1, [480000, 480000], 'any'],
            ['Head of Primary', 'PRI', null, [], 1, [380000, 380000], 'any'],
            ['Head of Early Years', 'EYR', null, [], 1, [330000, 330000], 'female'],
            ['Bursar', 'FIN', null, [], 1, [350000, 350000], 'any'],
            ['Accountant', 'FIN', null, [], 1, [240000, 240000], 'any'],
            ['Administrative Officer', 'ADM', null, [], 1, [180000, 180000], 'female'],
            ['Librarian', 'SUP', null, [], 1, [150000, 150000], 'female'],
            ['School Nurse', 'SUP', null, [], 1, [170000, 170000], 'female'],
            ['ICT Officer', 'SUP', null, [], 1, [200000, 200000], 'male'],
            ['Guidance Counsellor', 'SUP', null, [], 1, [210000, 210000], 'any'],
            ['Early Years Teacher', 'EYR', 'early', $early, 9, [110000, 160000], 'female'],
            ['Primary Teacher', 'PRI', 'primary', array_merge($primaryCore, ['Religious Studies (CRS/IRS)']), 13, [130000, 210000], 'any'],
            ['Computer Teacher', 'PRI', 'primary', ['Computer Studies'], 2, [150000, 190000], 'male'],
            ['Creative Arts & PHE Teacher', 'PRI', 'primary', ['Cultural & Creative Arts', 'Physical & Health Education', 'Home Economics'], 2, [130000, 170000], 'any'],
            ['Language & Agric Teacher', 'PRI', 'primary', ['Yoruba Language', 'Agricultural Science', 'Religious Studies (CRS/IRS)'], 2, [130000, 170000], 'any'],
            ['English Teacher', 'JSS', 'junior', ['English Language'], 3, [190000, 280000], 'any'],
            ['Mathematics Teacher', 'JSS', 'junior', ['Mathematics'], 3, [190000, 280000], 'male'],
            ['Basic Science Teacher', 'JSS', 'junior', ['Basic Science'], 2, [190000, 260000], 'any'],
            ['Basic Technology Teacher', 'JSS', 'junior', ['Basic Technology'], 2, [190000, 250000], 'male'],
            ['Humanities Teacher', 'JSS', 'junior', ['Social Studies', 'Civic Education', 'Business Studies'], 2, [180000, 250000], 'any'],
            ['Religious Studies & Arts Teacher', 'JSS', 'junior', ['Religious Studies (CRS/IRS)', 'Cultural & Creative Arts'], 2, [170000, 240000], 'any'],
            ['Computer & French Teacher', 'JSS', 'junior', ['Computer Studies', 'French'], 2, [190000, 260000], 'any'],
            ['Yoruba & Home Economics Teacher', 'JSS', 'junior', ['Yoruba Language', 'Home Economics', 'Agricultural Science'], 2, [170000, 230000], 'female'],
            ['PHE Teacher', 'JSS', 'junior', ['Physical & Health Education'], 2, [160000, 220000], 'male'],
            ['English Teacher', 'SSS', 'senior', ['English Language'], 2, [230000, 330000], 'any'],
            ['Mathematics Teacher', 'SSS', 'senior', ['Mathematics', 'Further Mathematics'], 3, [240000, 340000], 'male'],
            ['Physics Teacher', 'SSS', 'senior', ['Physics'], 2, [250000, 340000], 'male'],
            ['Chemistry Teacher', 'SSS', 'senior', ['Chemistry'], 2, [250000, 340000], 'any'],
            ['Biology Teacher', 'SSS', 'senior', ['Biology', 'Agricultural Science'], 2, [240000, 330000], 'female'],
            ['Literature & History Teacher', 'SSS', 'senior', ['Literature-in-English', 'History', 'Fine Art', 'Christian Religious Studies'], 2, [220000, 310000], 'any'],
            ['Government & Civic Teacher', 'SSS', 'senior', ['Government', 'Civic Education'], 2, [220000, 300000], 'any'],
            ['Economics & Commerce Teacher', 'SSS', 'senior', ['Economics', 'Commerce'], 2, [230000, 320000], 'any'],
            ['Accounting & Marketing Teacher', 'SSS', 'senior', ['Financial Accounting', 'Marketing'], 2, [230000, 320000], 'any'],
            ['ICT Teacher', 'SSS', 'senior', ['Computer Studies (ICT)'], 2, [230000, 310000], 'male'],
        ];

        $groupKeys = array_keys($this->groups);
        $weights = array_map(fn ($g) => $g['weight'], $this->groups);
        $teachers = [];
        $seq = 0;

        foreach ($plan as [$role, $dept, $level, $specs, $count, [$minPay, $maxPay], $genderHint]) {
            for ($k = 0; $k < $count; $k++) {
                $seq++;
                $gender = $genderHint === 'any' ? ($this->chance(0.5) ? 'male' : 'female') : $genderHint;
                $groupKey = $this->weighted($weights);
                $g = $this->groups[$groupKey];
                $first = $this->pick($g[$gender]);
                $last = $this->pick($g['surnames']);
                $muslim = $this->chance($g['muslim']);
                $dob = Carbon::create(mt_rand(1968, 1999), mt_rand(1, 12), mt_rand(1, 28));
                $salary = round(mt_rand($minPay, $maxPay) / 5000) * 5000;
                $status = $seq === 27 ? 'on_leave' : 'active';

                $id = $this->insertGetId('staff', [
                    'school_id' => $this->sid, 'user_id' => null,
                    'department_id' => $depts[$dept], 'designation_id' => $desig($role, $dept),
                    'emp_id' => sprintf('HCA/STF/%03d', $seq),
                    'first_name' => $first, 'last_name' => $last, 'gender' => $gender,
                    'date_of_birth' => $dob->toDateString(), 'blood_group' => $this->bloodGroup(),
                    'religion' => $muslim ? 'Islam' : 'Christianity', 'nationality' => 'Nigerian',
                    'phone' => $this->phone(),
                    'email' => strtolower(preg_replace('/[^A-Za-z]/', '', $first) . '.' . preg_replace('/[^A-Za-z]/', '', $last)) . '@heritagecrown.edu.ng',
                    'address' => $this->address(),
                    'joining_date' => Carbon::create(mt_rand(2012, 2025), $this->pick([1, 4, 9]), $this->pick([5, 12, 20]))->toDateString(),
                    'salary_type' => 'fixed', 'salary' => $salary, 'status' => $status,
                    'notes' => null,
                ]);

                $teachers[] = ['id' => $id, 'level' => $level, 'specs' => $specs, 'status' => $status];
            }
        }

        return $teachers;
    }

    // ───────────────────────── students ─────────────────────────

    private function newFamily(string $lastGroupKey = null): array
    {
        $weights = array_map(fn ($g) => $g['weight'], $this->groups);
        $key = $lastGroupKey ?? $this->weighted($weights);
        $g = $this->groups[$key];
        $muslim = $this->chance($g['muslim']);
        $surname = $this->pick($g['surnames']);

        $mums = array_merge($g['female'], $muslim ? NigerianNames::muslimMiddleNames()['female'] : NigerianNames::christianMiddleNames()['female']);
        $dads = array_merge($g['male'], $muslim ? NigerianNames::muslimMiddleNames()['male'] : NigerianNames::christianMiddleNames()['male']);
        $useMother = $this->chance(0.22);
        $parentFirst = $this->pick($useMother ? $mums : $dads);
        $relation = $useMother ? 'Mother' : 'Father';

        $title = $useMother ? $this->pick(['Mrs.', 'Mrs.', 'Mrs.', 'Dr. (Mrs.)', 'Barr. (Mrs.)', 'Mrs.']) : $this->pick(['Mr.', 'Mr.', 'Mr.', 'Dr.', 'Engr.', 'Barr.', 'Chief', 'Alhaji']);
        if ($muslim && ! $useMother && $title === 'Chief') {
            $title = 'Alhaji';
        }
        if (! $muslim && $title === 'Alhaji') {
            $title = 'Mr.';
        }

        $name = "$title $parentFirst $surname";
        $guardianId = $this->insertGetId('guardians', [
            'school_id' => $this->sid, 'user_id' => null, 'name' => $name, 'relation' => $relation,
            'phone' => $this->phone(),
            'email' => strtolower(preg_replace('/[^A-Za-z]/', '', $parentFirst) . '.' . preg_replace('/[^A-Za-z]/', '', $surname)) . mt_rand(1, 99) . '@' . $this->pick(['gmail.com', 'gmail.com', 'gmail.com', 'yahoo.com', 'outlook.com']),
            'occupation' => $this->pick(NigerianNames::occupations()),
            'address' => $this->address(),
            'photo' => null,
        ]);

        return ['id' => $guardianId, 'group' => $key, 'muslim' => $muslim, 'surname' => $surname, 'kids' => [], 'address' => null];
    }

    private function students(array $classes): array
    {
        $students = [];
        $rollByClass = [];
        $admitYear = fn (int $years) => 2026 - $years;
        $feederSchools = ['Bright Stars Nursery & Primary School, Surulere', 'Little Angels Montessori, Ikeja', 'Corona Schools, Victoria Island', 'Greensprings School, Lekki', 'Command Day Secondary School, Ikeja', 'Loyola Jesuit College, Abuja', 'Federal Government College, Ijanikin', 'Chrisland Schools, Opebi', 'Vivian Fowler Memorial College, Ikeja', 'Atlantic Hall, Epe'];

        foreach ($classes as $class) {
            $sectionNames = array_keys($class['sections']);
            $rollByClass[$class['id']] = 0;

            for ($i = 0; $i < $class['count']; $i++) {
                // siblings: ~16% of children join an existing family in a different class
                $family = null;
                if ($this->chance(0.16)) {
                    $candidates = array_values(array_filter($this->families, fn ($f) => count($f['kids']) < 3 && ! in_array($class['id'], $f['kids'], true)));
                    if ($candidates) {
                        $idx = array_search($this->pick($candidates), $this->families, true);
                        $family = &$this->families[$idx];
                    }
                }
                if ($family === null) {
                    $this->families[] = $this->newFamily();
                    $family = &$this->families[array_key_last($this->families)];
                }
                $g = $this->groups[$family['group']];

                $gender = $this->chance(0.5) ? 'male' : 'female';
                $given = $this->pick($g[$gender]);
                $mid = $this->chance(0.55)
                    ? ' ' . $this->pick($family['muslim'] ? NigerianNames::muslimMiddleNames()[$gender] : NigerianNames::christianMiddleNames()[$gender])
                    : '';
                if (strtolower(trim($mid)) === strtolower($given)) {
                    $mid = '';
                }

                $sectionName = $class['level'] === 'senior'
                    ? $this->weighted(['Science' => 45, 'Arts' => 22, 'Commercial' => 33])
                    : $sectionNames[$i % count($sectionNames)];

                $age = $class['age'];
                $dob = Carbon::create(2026 - $age, mt_rand(1, 12), mt_rand(1, 28))->subYears($this->chance(0.3) ? 1 : 0);
                $yearsInSchool = min($class['index'], mt_rand(0, 4));
                $admitted = Carbon::create($admitYear($yearsInSchool), 9, mt_rand(7, 20));
                $this->admissionCounters[$admitted->year] = ($this->admissionCounters[$admitted->year] ?? 0) + 1;

                $isEntry = $yearsInSchool === 0 && in_array($class['name'], ['Primary 1', 'JSS 1', 'SS 1', 'Primary 4', 'JSS 2'], true);

                $rollByClass[$class['id']]++;
                $studentId = $this->insertGetId('students', [
                    'school_id' => $this->sid, 'user_id' => null, 'class_id' => $class['id'],
                    'section_id' => $class['sections'][$sectionName], 'guardian_id' => $family['id'],
                    'admission_no' => sprintf('HCA/%d/%04d', $admitted->year, $this->admissionCounters[$admitted->year]),
                    'roll_no' => (string) $rollByClass[$class['id']],
                    'first_name' => $given . $mid, 'last_name' => $family['surname'],
                    'gender' => $gender, 'date_of_birth' => $dob->toDateString(),
                    'blood_group' => $this->bloodGroup(),
                    'religion' => $family['muslim'] ? 'Islam' : 'Christianity',
                    'nationality' => 'Nigerian', 'phone' => null, 'email' => null,
                    'address' => $family['address'] ??= $this->address(),
                    'photo' => null, 'category' => $this->chance(0.012) ? 'disabled' : 'general', 'status' => 'active',
                    'admission_date' => $admitted->toDateString(),
                    'previous_school' => $isEntry ? $this->pick($feederSchools) : null,
                ]);

                $family['kids'][] = $class['id'];
                $students[] = [
                    'id' => $studentId, 'class_id' => $class['id'], 'level' => $class['level'], 'section' => $sectionName,
                    'ability' => max(0.38, min(0.94, $this->gauss(0.66, 0.12))),
                    'siblings' => count($family['kids']) > 1,
                    'family_ref' => $family['id'],
                ];
                unset($family);
            }
        }

        // brothers and sisters get the sibling discount, whichever child was added first
        $familyCounts = array_count_values(array_column($students, 'family_ref'));
        foreach ($students as &$s) {
            $s['siblings'] = ($familyCounts[$s['family_ref']] ?? 1) > 1;
        }

        return $students;
    }

    // ───────────────────────── holidays & grading ─────────────────────────

    private function holidays(): void
    {
        $rows = [
            ['2026-10-01', 'Independence Day', 'Nigeria\'s 66th Independence anniversary. School closed.'],
            ['2026-10-26', 'Mid-Term Break (Day 1)', 'First term mid-term break begins.'],
            ['2026-10-27', 'Mid-Term Break (Day 2)', null],
            ['2026-10-28', 'Mid-Term Break (Day 3)', null],
            ['2026-10-29', 'Mid-Term Break (Day 4)', null],
            ['2026-10-30', 'Mid-Term Break (Day 5)', 'Classes resume Monday.'],
            ['2026-12-25', 'Christmas Day', null],
            ['2026-12-26', 'Boxing Day', null],
            ['2027-01-01', 'New Year\'s Day', null],
            ['2027-03-10', 'Eid-el-Fitr', 'Date follows the moon sighting.'],
            ['2027-03-26', 'Good Friday', null],
            ['2027-03-29', 'Easter Monday', null],
            ['2027-05-01', 'Workers\' Day', null],
            ['2027-05-17', 'Eid-el-Kabir', 'Date follows the moon sighting.'],
            ['2027-06-12', 'Democracy Day', null],
        ];

        $this->insert('holidays', array_map(fn ($r) => [
            'school_id' => $this->sid, 'name' => $r[1], 'date' => $r[0], 'description' => $r[2],
        ], $rows));
    }

    /**
     * WAEC grades (A1 – F9) are the school default. Nursery and primary
     * classes use the simpler A – F scale, as many Nigerian schools do.
     *
     * @return array<int, array> grade bands per class id, as [grade, gpa, min %]
     */
    private function gradeScales(array $classes): array
    {
        $simpleId = SchoolDefaults::createGradingScheme($this->sid, 'simple');
        $asRows = fn (string $preset) => array_map(fn ($b) => [$b[0], $b[4], $b[1]], SchoolDefaults::GRADING_PRESETS[$preset]['bands']);

        $byClass = [];
        foreach ($classes as $class) {
            $simple = in_array($class['level'], ['early', 'primary'], true);
            if ($simple) {
                DB::table('classes')->where('id', $class['id'])->update(['grading_scheme_id' => $simpleId]);
            }
            $byClass[$class['id']] = $asRows($simple ? 'simple' : 'waec');
        }

        return $byClass;
    }

    private function gradeFor(float $marks, array $scale): array
    {
        foreach ($scale as [$grade, $gpa, $min]) {
            if ($marks >= $min) {
                return [$grade, $gpa];
            }
        }

        return [end($scale)[0], 0.00];
    }

    // ───────────────────────── exams & results ─────────────────────────

    private function exams(array $classes, array $subjects, array $students, array $scales): void
    {
        $byClass = [];
        foreach ($students as $s) {
            $byClass[$s['class_id']][] = $s;
        }

        // A few subjects are harder than others, so results look natural
        $bias = ['Mathematics' => -6, 'Further Mathematics' => -8, 'Physics' => -5, 'Chemistry' => -4, 'English Language' => -2, 'Physical & Health Education' => 9, 'Cultural & Creative Arts' => 8, 'Computer Studies' => 5, 'Creative Arts & Colouring' => 9, 'Religious Studies (CRS/IRS)' => 6];

        // [year, term number] each exam belongs to
        $termId = fn (string $year, int $seq) => DB::table('terms')->where('school_id', $this->sid)->where('sequence', $seq)
            ->where('academic_year_id', DB::table('academic_years')->where('school_id', $this->sid)->where('name', $year)->value('id'))->value('id');
        $terms = [$termId('2025/2026', 3), $termId('2026/2027', 1), $termId('2026/2027', 1)];

        $examPlan = [
            ['Third Term Examination 2025/2026', 'final', '2026-07-06', '2026-07-17', 'completed', 1.0, 'End of session examination.'],
            ['First Term Continuous Assessment 1, 2026/2027', 'unit_test', '2026-09-28', '2026-10-02', 'completed', 0.6, 'First continuous assessment, 40 marks scaled to 100.'],
            ['First Term Examination 2026/2027', 'final', '2026-12-07', '2026-12-16', 'draft', null, 'Scheduled. Timetable will be shared with parents.'],
        ];

        $markRows = [];
        foreach ($classes as $class) {
            foreach ($examPlan as $p => [$name, $type, $start, $end, $status, $effort, $desc]) {
                $examId = $this->insertGetId('exams', [
                    'school_id' => $this->sid, 'class_id' => $class['id'], 'term_id' => $terms[$p], 'name' => $name, 'type' => $type,
                    'start_date' => $start, 'end_date' => $end, 'status' => $status, 'description' => $desc,
                ]);

                if ($status === 'draft') {
                    continue;
                }

                foreach ($byClass[$class['id']] ?? [] as $student) {
                    foreach ($this->subjectsFor($subjects[$class['id']], $student['section'], $class['level']) as $subject) {
                        $absent = $this->chance(0.006);
                        $raw = $student['ability'] * 100 + ($bias[$subject['name']] ?? 0) + $this->gauss(0, 9);
                        // CA scores run a little lower than final exam scores
                        $marks = $absent ? null : round(max(9, min(99, $raw - ($effort < 1 ? 4 : 0))) * 2) / 2;
                        [$grade, $gpa] = $marks === null ? [null, null] : $this->gradeFor($marks, $scales[$class['id']]);

                        $markRows[] = [
                            'school_id' => $this->sid, 'exam_id' => $examId, 'student_id' => $student['id'],
                            'subject_id' => $subject['id'], 'marks_obtained' => $marks, 'grade' => $grade,
                            'gpa' => $gpa, 'is_absent' => $absent, 'remarks' => null,
                        ];
                    }
                }
            }
        }

        $this->insert('marks', $markRows);
    }

    // ───────────────────────── term results ─────────────────────────

    /**
     * Last session's Third Term is finished: CA1 + CA2 + Exam, behaviour
     * ratings, positions, published and locked. This term has CA1 only, in draft.
     */
    private function termResults(array $classes): void
    {
        $termId = fn (string $year, int $seq) => DB::table('terms')->where('school_id', $this->sid)->where('sequence', $seq)
            ->where('academic_year_id', DB::table('academic_years')->where('school_id', $this->sid)->where('name', $year)->value('id'))->value('id');
        $lastTerm = $termId('2025/2026', 3);
        $thisTerm = $termId('2026/2027', 1);

        $schemeId = DB::table('assessment_schemes')->where('school_id', $this->sid)->where('is_default', true)->value('id');
        $part = DB::table('assessment_components')->where('assessment_scheme_id', $schemeId)->pluck('id', 'short_name');
        $traits = DB::table('behaviour_traits')->where('school_id', $this->sid)->pluck('id');
        $service = new TermResultService();
        $clamp = fn (float $v, float $max) => max(0, min($max, round($v * 2) / 2));

        foreach ($classes as $class) {
            $marksFor = fn (string $exam) => DB::table('marks')->join('exams', 'exams.id', '=', 'marks.exam_id')
                ->where('exams.school_id', $this->sid)->where('exams.class_id', $class['id'])->where('exams.name', $exam)
                ->whereNotNull('marks.marks_obtained')->get(['marks.student_id', 'marks.subject_id', 'marks.marks_obtained']);

            // Third Term 2025/2026: the exam mark (out of 100) split into CA1, CA2 and Exam
            $last = $this->insertGetId('result_sheets', [
                'school_id' => $this->sid, 'term_id' => $lastTerm, 'class_id' => $class['id'], 'status' => 'locked',
                'submitted_by' => $this->adminUserId, 'submitted_at' => '2026-07-20 10:00:00',
                'approved_by' => $this->adminUserId, 'approved_at' => '2026-07-21 09:00:00',
                'published_by' => $this->adminUserId, 'published_at' => '2026-07-22 09:00:00',
                'locked_by' => $this->adminUserId, 'locked_at' => '2026-08-01 09:00:00',
            ]);
            $scores = [];
            $studentIds = [];
            foreach ($marksFor('Third Term Examination 2025/2026') as $m) {
                $pct = (float) $m->marks_obtained;
                $studentIds[$m->student_id] = true;
                foreach ([['CA1', 20], ['CA2', 20], ['Exam', 60]] as [$short, $max]) {
                    $scores[] = [
                        'school_id' => $this->sid, 'result_sheet_id' => $last, 'student_id' => $m->student_id, 'subject_id' => $m->subject_id,
                        'assessment_component_id' => $part[$short],
                        'score' => $clamp(($pct + ($short === 'Exam' ? 0 : $this->gauss(2, 6))) * $max / 100, $max),
                    ];
                }
            }
            $this->insert('subject_scores', $scores);

            $ratings = [];
            foreach (array_keys($studentIds) as $studentId) {
                foreach ($traits as $traitId) {
                    $ratings[] = [
                        'school_id' => $this->sid, 'result_sheet_id' => $last, 'student_id' => $studentId,
                        'behaviour_trait_id' => $traitId, 'rating' => (int) $this->weighted(['5' => 30, '4' => 42, '3' => 22, '2' => 6]),
                    ];
                }
            }
            $this->insert('behaviour_ratings', $ratings);

            // First Term 2026/2027: only CA1 so far, still being entered
            $current = $this->insertGetId('result_sheets', [
                'school_id' => $this->sid, 'term_id' => $thisTerm, 'class_id' => $class['id'], 'status' => 'draft',
            ]);
            $this->insert('subject_scores', $marksFor('First Term Continuous Assessment 1, 2026/2027')->map(fn ($m) => [
                'school_id' => $this->sid, 'result_sheet_id' => $current, 'student_id' => $m->student_id, 'subject_id' => $m->subject_id,
                'assessment_component_id' => $part['CA1'], 'score' => $clamp((float) $m->marks_obtained * 0.2, 20),
            ])->all());

            foreach ([$last, $current] as $sheetId) {
                $service->compute(ResultSheet::findOrFail($sheetId));
            }
        }
    }

    // ───────────────────────── fees (naira) ─────────────────────────

    private function fees(array $classes, array $students): void
    {
        $cats = [];
        foreach ([
            ['Tuition Fee', 'tuition', 'Termly school fees'],
            ['Examination & CA Fee', 'exam', 'Continuous assessment, examination and report cards'],
            ['Development Levy', 'other', 'Annual levy for facilities, ICT lab and building projects'],
            ['Library Fee', 'library', 'Annual library and reading programme'],
            ['Sports & Clubs Fee', 'sports', 'Inter-house sports, clubs and societies'],
            ['School Bus Fee', 'transport', 'Termly school bus service'],
        ] as [$name, $type, $desc]) {
            $cats[$type . ($type === 'other' ? '' : '')] = $this->insertGetId('fee_categories', [
                'school_id' => $this->sid, 'name' => $name, 'description' => $desc, 'type' => $type, 'is_active' => true,
            ]);
        }

        $tuition = [165000, 185000, 195000, 205000, 245000, 250000, 260000, 270000, 285000, 300000, 340000, 350000, 360000, 395000, 410000, 440000];
        $structures = [];
        foreach ($classes as $i => $class) {
            $examFee = [$class['level'] === 'senior' ? 25000 : ($class['level'] === 'junior' ? 18000 : ($class['level'] === 'primary' ? 14000 : 10000))][0];
            $defs = [
                'tuition' => [$tuition[$i], 'quarterly', '2026-09-21', 'First term fees 2026/2027'],
                'exam' => [$examFee, 'one_time', '2026-10-05', 'First term examination and CA fee'],
                'other' => [50000, 'annual', '2026-09-21', 'Development levy 2026/2027'],
                'library' => [8000, 'annual', '2026-10-05', null],
                'sports' => [10000, 'annual', '2026-10-05', null],
                'transport' => [95000, 'quarterly', '2026-09-21', 'Lekki, Ikeja and Mainland routes'],
            ];
            foreach ($defs as $type => [$amount, $freq, $due, $desc]) {
                $structures[$class['id']][$type] = [
                    'id' => $this->insertGetId('fee_structures', [
                        'school_id' => $this->sid, 'class_id' => $class['id'], 'fee_category_id' => $cats[$type],
                        'academic_year' => '2026/2027', 'amount' => $amount, 'due_date' => $due,
                        'frequency' => $freq, 'description' => $desc, 'is_active' => true,
                    ]),
                    'amount' => $amount,
                ];
            }
        }

        $receipt = 0;
        $rows = [];
        $start = Carbon::create(2026, 9, 7);
        foreach ($students as $student) {
            $taking = ['tuition', 'exam', 'other'];
            if ($this->chance(0.24)) {
                $taking[] = 'transport';
            }
            if ($this->chance(0.55)) {
                $taking[] = 'library';
            }
            if ($this->chance(0.5)) {
                $taking[] = 'sports';
            }

            // Most families pay in full; some pay in instalments; a few have not paid yet
            $habit = $this->weighted(['full' => 62, 'part' => 20, 'late' => 18]);

            foreach ($taking as $type) {
                $def = $structures[$student['class_id']][$type];
                $due = (float) $def['amount'];
                $discount = ($type === 'tuition' && $student['siblings']) ? round($due * 0.05, 2) : 0.0;
                $net = $due - $discount;

                $status = match ($habit) {
                    'full' => 'paid',
                    'part' => $type === 'tuition' ? 'partial' : 'paid',
                    default => $type === 'tuition' ? $this->pick(['overdue', 'overdue', 'pending']) : ($this->chance(0.5) ? 'paid' : 'pending'),
                };

                $paid = match ($status) {
                    'paid' => $net,
                    'partial' => round($net * $this->pick([0.4, 0.5, 0.5, 0.6, 0.75]) / 5000) * 5000,
                    default => 0.0,
                };
                $fine = $status === 'overdue' ? 5000 : 0;
                $date = $paid > 0 ? $start->copy()->addDays(mt_rand(0, 30))->toDateString() : null;
                $receipt++;

                $rows[] = [
                    'school_id' => $this->sid, 'student_id' => $student['id'], 'fee_structure_id' => $def['id'],
                    'receipt_no' => sprintf('HCA-RCP-%06d', $receipt),
                    'amount_due' => $due, 'amount_paid' => $paid, 'discount' => $discount, 'fine' => $fine,
                    'payment_date' => $date, 'month_year' => '2026-T1',
                    'method' => $this->weighted(['online' => 52, 'cash' => 26, 'card' => 22]),
                    'status' => $status,
                    'note' => $discount > 0 ? '5% sibling discount applied' : ($status === 'partial' ? 'First instalment. Balance to be paid before the examination.' : null),
                ];
            }
        }

        $this->insert('fee_payments', $rows);
    }

    // ───────────────────────── attendance ─────────────────────────

    private function attendance(array $students, array $teachers): void
    {
        $holidays = DB::table('holidays')->where('school_id', $this->sid)->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();
        $days = [];
        $cursor = $this->now->copy()->startOfDay();
        while (count($days) < 25) {
            $cursor->subDay();
            if ($cursor->isWeekend() || in_array($cursor->toDateString(), $holidays, true)) {
                continue;
            }
            $days[] = $cursor->toDateString();
        }

        $year = DB::table('academic_years')->where('school_id', $this->sid)->where('is_current', true)->value('id');
        $rows = [];

        // a few children are often late or absent, which makes the reports interesting
        $patterns = [];
        foreach ($students as $s) {
            $patterns[$s['id']] = $this->weighted(['good' => 80, 'late' => 9, 'sickly' => 8, 'poor' => 3]);
        }

        foreach ($days as $date) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            foreach ($students as $s) {
                $p = $patterns[$s['id']];
                $absent = match ($p) { 'good' => 0.025, 'late' => 0.03, 'sickly' => 0.12, default => 0.28 };
                $late = match ($p) { 'good' => 0.025, 'late' => 0.22, 'sickly' => 0.04, default => 0.1 };
                // Mondays and Fridays see slightly more absence
                if (in_array($dayOfWeek, [1, 5], true)) {
                    $absent += 0.015;
                }
                $r = mt_rand() / mt_getrandmax();
                $status = $r < $absent ? 'absent' : ($r < $absent + $late ? 'late' : ($r < $absent + $late + 0.008 ? 'half_day' : 'present'));

                $rows[] = [
                    'school_id' => $this->sid, 'academic_year_id' => $year, 'date' => $date,
                    'attendable_type' => 'App\\Models\\Student', 'attendable_id' => $s['id'], 'status' => $status,
                    'remarks' => $status === 'absent' && $this->chance(0.4) ? $this->pick(['Malaria', 'Sick, parent called', 'Family event', 'Travel', 'Medical appointment']) : null,
                ];
            }
            foreach ($teachers as $t) {
                $r = mt_rand() / mt_getrandmax();
                $rows[] = [
                    'school_id' => $this->sid, 'academic_year_id' => $year, 'date' => $date,
                    'attendable_type' => 'App\\Models\\Staff', 'attendable_id' => $t['id'],
                    'status' => $t['status'] === 'on_leave' ? 'absent' : ($r < 0.03 ? 'absent' : ($r < 0.1 ? 'late' : 'present')),
                    'remarks' => $t['status'] === 'on_leave' ? 'On approved leave' : null,
                ];
            }
        }

        $this->insert('attendances', $rows);
    }

    // ───────────────────────── timetable ─────────────────────────

    private function timetable(array $classes, array $subjects, array $teachers): void
    {
        $periods = [['08:00', '08:40'], ['08:40', '09:20'], ['09:20', '10:00'], ['10:30', '11:10'], ['11:10', '11:50'], ['11:50', '12:30'], ['12:30', '13:10']];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $busy = []; // teacher|day|start
        $rows = [];

        foreach ($classes as $class) {
            $periodCount = $class['level'] === 'early' ? 5 : ($class['level'] === 'primary' ? 6 : 7);
            $sectionIndex = 0;

            foreach ($class['sections'] as $sectionName => $sectionId) {
                $own = array_values($this->subjectsFor($subjects[$class['id']], $sectionName, $class['level']));
                // English and Mathematics come up most days
                $sequence = array_merge($own, array_filter($own, fn ($s) => in_array($s['name'], ['English Language', 'English Studies', 'Mathematics', 'Number Work'], true)));
                $sequence = array_values($sequence);
                $n = count($sequence);

                foreach ($days as $d => $day) {
                    for ($p = 0; $p < $periodCount; $p++) {
                        $subject = $sequence[($d * 3 + $p + $class['index'] * 5 + $sectionIndex * 2) % $n];
                        [$start, $end] = $periods[$p];

                        $candidates = array_values(array_filter($teachers, fn ($t) => $t['level'] === $class['level'] && in_array($subject['name'], $t['specs'], true) && $t['status'] === 'active'));
                        shuffle($candidates);
                        $teacherId = null;
                        foreach ($candidates as $c) {
                            if (! isset($busy["{$c['id']}|$day|$start"])) {
                                $teacherId = $c['id'];
                                $busy["{$c['id']}|$day|$start"] = true;
                                break;
                            }
                        }

                        $rows[] = [
                            'school_id' => $this->sid, 'class_id' => $class['id'], 'section_id' => $sectionId,
                            'subject_id' => $subject['id'], 'teacher_id' => $teacherId, 'day_of_week' => $day,
                            'start_time' => $start . ':00', 'end_time' => $end . ':00',
                            'room' => $class['name'] . ' ' . $sectionName, 'notes' => null,
                        ];
                    }
                }
                $sectionIndex++;
            }
        }

        $this->insert('timetables', $rows);
    }

    // ───────────────────────── homework ─────────────────────────

    private function homework(array $classes, array $subjects, array $teachers): void
    {
        $templates = [
            'Number Work' => ['Count and write the numbers 1 to 20 in your notebook.', 'Add the pictures and write the answers (page 14).'],
            'Letter Work & Phonics' => ['Trace and colour the letters A to E and say one word for each.', 'Practise the sounds s, a, t, p at home.'],
            'Rhymes & Songs' => ['Learn the rhyme "Twinkle, Twinkle, Little Star" for Friday.'],
            'English Studies' => ['Write five sentences about "My Family" and underline the nouns.', 'Read "The Tortoise and the Hare" and answer questions 1 to 5.'],
            'Mathematics' => ['Solve questions 1 to 10 on page 38 and show your working.', 'Learn the 7 and 8 times tables and be ready for a quiz.'],
            'Basic Science & Technology' => ['Draw and label the parts of a flowering plant.', 'List five sources of water in your community.'],
            'Social Studies' => ['Name the six geo-political zones of Nigeria and one state in each.'],
            'Civic Education' => ['Write a short note on "Why we must obey school rules".'],
            'Yoruba Language' => ['Ka ìtàn "Ìjàpá àti Ọ̀kẹ́rẹ́" kí o sì dáhùn àwọn ìbéèrè márùn-ún.'],
            'English Language' => ['Write a formal letter to your Principal requesting a school library period (250 words).', 'Read Chapter 3 of your literature text and summarise it in one page.'],
            'Basic Science' => ['Explain the difference between a mixture and a compound with two examples each.'],
            'Business Studies' => ['Prepare a simple cash book for a provision store for one week.'],
            'Physics' => ['Solve problems 1 to 8 on motion in a straight line, page 52.'],
            'Chemistry' => ['Balance the ten chemical equations on the worksheet and submit on Monday.'],
            'Biology' => ['Draw and label the human digestive system and explain the function of each part.'],
            'Further Mathematics' => ['Complete Exercise 6B on matrices (questions 1 to 12).'],
            'Literature-in-English' => ['Write a character sketch of Okonkwo from "Things Fall Apart" (300 words).'],
            'Government' => ['Explain three features of a federal system of government using Nigeria as an example.'],
            'Economics' => ['Draw a demand curve for garri using the data in your note and explain its shape.'],
            'Financial Accounting' => ['Prepare a trial balance from the ledger balances given in class.'],
            'Commerce' => ['Write a note on the functions of the Central Bank of Nigeria.'],
            'Computer Studies' => ['Write down five parts of a computer and what each does.'],
            'Computer Studies (ICT)' => ['Create a spreadsheet of your class test scores and calculate the average.'],
        ];

        $rows = [];
        foreach ($classes as $class) {
            $teaching = array_values($subjects[$class['id']]);
            $chosen = [];
            foreach ($teaching as $s) {
                if (isset($templates[$s['name']])) {
                    $chosen[] = $s;
                }
            }
            shuffle($chosen);
            foreach (array_slice($chosen, 0, 3) as $k => $subject) {
                $teacher = null;
                foreach ($teachers as $t) {
                    if ($t['level'] === $class['level'] && in_array($subject['name'], $t['specs'], true)) {
                        $teacher = $t['id'];
                        break;
                    }
                }
                $title = $this->pick($templates[$subject['name']]);
                $rows[] = [
                    'school_id' => $this->sid, 'class_id' => $class['id'], 'subject_id' => $subject['id'], 'teacher_id' => $teacher,
                    'title' => mb_strimwidth($title, 0, 80, '…'), 'description' => $title,
                    'due_date' => $this->now->copy()->addDays(2 + $k * 2)->toDateString(), 'attachment' => null, 'is_active' => true,
                ];
            }
        }

        $this->insert('homework', $rows);
    }

    // ───────────────────────── announcements ─────────────────────────

    private function announcements(array $classes): void
    {
        $items = [
            ['Welcome back to First Term 2026/2027', 'Dear parents and guardians, we welcome you and your wards to a new session. School hours are 8:00 am to 1:10 pm for Primary and Secondary, and 8:00 am to 12:30 pm for Early Years. Please ensure every child is in the correct uniform.', true, 35],
            ['Independence Day holiday', 'The school will be closed on Thursday, 1st October, for Independence Day. Classes resume on Friday.', false, 9],
            ['Inter-House Sports Day: Saturday', 'Our Inter-House Sports Day holds at the Teslim Balogun Stadium grounds. Gates open by 8:00 am. Children should wear their house colours: Red (Sapphire), Blue (Emerald), Yellow (Topaz) and Green (Ruby). Parents are warmly invited.', true, 6],
            ['PTA meeting', 'The first Parent-Teacher Association meeting of the term holds this Saturday at 10:00 am in the school hall. Agenda: school fees, security, the new school bus routes and the Christmas carol service.', true, 4],
            ['First Term fees reminder', 'Parents with outstanding first term fees are kindly asked to settle them before the Continuous Assessment results are released. Payments can be made online or at the Bursary. Please bring your receipt for confirmation.', false, 3],
            ['JSS 3 BECE registration', 'Registration for the BECE is now open. JSS 3 parents should submit a passport photograph and a copy of the birth certificate to the Admin Office by the end of the month.', false, 2],
            ['SS 3 mock WAEC and NECO timetable', 'The mock examination timetable for SS 3 has been shared. Students should collect their candidates\' cards from their form teachers. Punctuality is compulsory.', false, 1],
            ['Excursion to Lekki Conservation Centre', 'Primary 3 to Primary 5 pupils will visit the Lekki Conservation Centre next Thursday. Please sign the consent slip and send ₦6,000 with your child for the trip. Packed lunch and water bottles are required.', false, 0],
        ];

        $this->insert('announcements', array_map(fn ($a) => [
            'school_id' => $this->sid, 'author_id' => $this->adminUserId, 'title' => $a[0], 'body' => $a[1],
            'audience' => 'all', 'class_id' => null, 'target_role' => null, 'is_pinned' => $a[2],
            'published_at' => $this->now->copy()->subDays($a[3])->setTime(9, 0)->toDateTimeString(),
        ], $items));
    }

    // ───────────────────────── library ─────────────────────────

    private function library(): void
    {
        $books = [
            ['Things Fall Apart', 'Chinua Achebe', 'Literature', 'Heinemann', 1958],
            ['Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', 'Literature', 'Fourth Estate', 2006],
            ['Purple Hibiscus', 'Chimamanda Ngozi Adichie', 'Literature', 'Algonquin Books', 2003],
            ['The Lion and the Jewel', 'Wole Soyinka', 'Drama', 'Oxford University Press', 1963],
            ['Death and the King\'s Horseman', 'Wole Soyinka', 'Drama', 'Methuen', 1975],
            ['The Joys of Motherhood', 'Buchi Emecheta', 'Literature', 'Heinemann', 1979],
            ['Efuru', 'Flora Nwapa', 'Literature', 'Heinemann', 1966],
            ['Born on a Tuesday', 'Elnathan John', 'Literature', 'Cassava Republic', 2015],
            ['Americanah', 'Chimamanda Ngozi Adichie', 'Literature', 'Knopf', 2013],
            ['The Famished Road', 'Ben Okri', 'Literature', 'Jonathan Cape', 1991],
            ['Anthills of the Savannah', 'Chinua Achebe', 'Literature', 'Heinemann', 1987],
            ['The Secret Lives of Baba Segi\'s Wives', 'Lola Shoneyin', 'Literature', 'Serpent\'s Tail', 2010],
            ['Sundiata: An Epic of Old Mali', 'D. T. Niane', 'History', 'Longman', 1960],
            ['Efunsetan Aniwura', 'D. O. Fagunwa', 'Yoruba Literature', 'Nelson', 1960],
            ['Ogboju Ode Ninu Igbo Irunmale', 'D. O. Fagunwa', 'Yoruba Literature', 'Nelson', 1938],
            ['New General Mathematics for JSS 1', 'M. F. Macrae et al.', 'Mathematics', 'Longman', 2012],
            ['Essential Mathematics for SS 2', 'A. J. S. Oluwasanmi', 'Mathematics', 'Evans', 2014],
            ['New School Chemistry', 'Osei Yaw Ababio', 'Science', 'Africana First Publishers', 2009],
            ['Senior Secondary Physics', 'Nelkon & Parker', 'Science', 'Heinemann', 2008],
            ['Biology for Senior Secondary Schools', 'Stella Ramalingam', 'Science', 'Learn Africa', 2010],
            ['Oxford English for Primary Schools 4', 'Various authors', 'English', 'Oxford University Press', 2015],
            ['Verbal Reasoning Practice, Book 3', 'Ibrahim Adamu', 'Aptitude', 'Tonad Publishers', 2016],
            ['Nigerian Folktales for Children', 'Ola Rotimi', 'Children', 'Spectrum Books', 2005],
            ['The Gods Are Not to Blame', 'Ola Rotimi', 'Drama', 'Oxford University Press', 1971],
            ['A Dictionary of Nigerian English Usage', 'Ayo Bamgbose', 'Reference', 'Ibadan University Press', 1995],
            ['Atlas for Nigerian Schools', 'Macmillan Nigeria', 'Reference', 'Macmillan', 2018],
        ];

        $rows = [];
        foreach ($books as $i => [$title, $author, $category, $publisher, $year]) {
            $copies = mt_rand(3, 14);
            $rows[] = [
                'school_id' => $this->sid, 'isbn' => '978-978-' . mt_rand(10000, 99999) . '-' . mt_rand(1, 9),
                'title' => $title, 'author' => $author, 'category' => $category, 'publisher' => $publisher,
                'publication_year' => $year, 'location' => 'Shelf ' . chr(65 + $i % 6) . '-' . (1 + intdiv($i, 6)),
                'total_copies' => $copies, 'available_copies' => max(1, $copies - mt_rand(0, 3)),
                'cover' => null, 'description' => null, 'is_active' => true,
            ];
        }

        $this->insert('books', $rows);
    }
}
