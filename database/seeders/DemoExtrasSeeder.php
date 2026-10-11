<?php

namespace Database\Seeders;

use App\Support\DemoSchool;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Sample data for the "side" departments of the demo school (Heritage Crown Academy, Lagos):
 * hostels, school buses, library borrowing, the store, assets, the front desk, admission
 * inquiries, and the bell notices for every demo account.
 *
 * It only touches its own tables, so it can be run again at any time without
 * disturbing the students, fees or results.  `demo:fill` runs it on every deploy.
 */
class DemoExtrasSeeder extends Seeder
{
    private int $sid;
    private Carbon $now;

    public function run(): void
    {
        if (! config('app.demo_enabled')) {
            return;
        }

        $school = DemoSchool::school();
        $this->sid = $school->id;
        $this->now = Carbon::now();

        $students = DB::table('students')->where('school_id', $this->sid)->where('status', 'active')
            ->orderBy('date_of_birth')->get(['id', 'gender', 'class_id', 'first_name', 'last_name']);
        $staff = DB::table('staff')->where('school_id', $this->sid)->get(['id', 'gender', 'first_name', 'last_name']);
        if ($students->isEmpty()) {
            return; // nothing to attach to yet
        }

        mt_srand(20261011);

        DB::transaction(function () use ($students, $staff) {
            $this->wipe();
            $this->hostels($students, $staff);
            $this->transport($students);
            $this->libraryLoans($students, $staff);
            $this->store($staff);
            $this->assets();
            $this->frontDesk();
            $this->roleNotices();
        });
        $this->notifications();

        $this->command?->info('Demo extras filled: hostels, buses, library loans, store, front desk and notices.');
    }

    private function stamp(array $rows): array
    {
        $t = $this->now->toDateTimeString();

        return array_map(fn ($r) => $r + ['created_at' => $t, 'updated_at' => $t], $rows);
    }

    private function put(string $table, array $rows): void
    {
        foreach (array_chunk($this->stamp($rows), 300) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function putGetId(string $table, array $row): int
    {
        return (int) DB::table($table)->insertGetId($this->stamp([$row])[0]);
    }

    private function phone(): string
    {
        return ['0803', '0806', '0810', '0813', '0816', '0703', '0802', '0808', '0902', '0909'][mt_rand(0, 9)] . mt_rand(1000000, 9999999);
    }

    private function pick(array $list): mixed
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    private function wipe(): void
    {
        $sid = $this->sid;
        $routeIds = DB::table('routes')->where('school_id', $sid)->pluck('id');
        if ($routeIds->isNotEmpty()) {
            DB::table('student_route')->whereIn('route_id', $routeIds)->delete();
        }
        $inquiryIds = DB::table('admission_inquiries')->where('school_id', $sid)->pluck('id');
        if ($inquiryIds->isNotEmpty()) {
            DB::table('inquiry_followups')->whereIn('inquiry_id', $inquiryIds)->delete();
        }

        foreach ([
            'hostel_allocations', 'hostel_rooms', 'hostels', 'routes', 'vehicles',
            'book_issues', 'inventory_issues', 'inventory_purchases', 'inventory_items', 'inventory_categories',
            'asset_maintenance_logs', 'assets', 'visitor_logs', 'admission_assessments', 'admission_inquiries', 'school_notifications',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                DB::table($table)->where('school_id', $sid)->delete();
            }
        }
        DB::table('announcements')->where('school_id', $sid)->where('audience', 'role')->delete();
    }

    // ───────────────────────── hostels ─────────────────────────

    private function hostels($students, $staff): void
    {
        $houses = [
            ['Azikiwe House (Boys)', 'boys', 'male', 'Boys boarding wing, behind the Sports Hall'],
            ['Moremi House (Girls)', 'girls', 'female', 'Girls boarding wing, beside the Chapel'],
        ];

        foreach ($houses as [$name, $type, $gender, $address]) {
            $warden = $staff->firstWhere('gender', $gender);
            $hostelId = $this->putGetId('hostels', [
                'school_id' => $this->sid, 'name' => $name, 'type' => $type, 'warden_id' => $warden->id ?? null,
                'address' => $address, 'total_rooms' => 0, 'total_capacity' => 0, 'status' => 'active',
            ]);

            $layout = [
                ['G01', 'Ground', 'dormitory', 8, false, 25000], ['G02', 'Ground', 'dormitory', 8, false, 25000],
                ['G03', 'Ground', 'double', 2, false, 35000], ['G04', 'Ground', 'double', 2, false, 35000],
                ['101', '1st', 'dormitory', 6, false, 28000], ['102', '1st', 'dormitory', 6, false, 28000],
                ['103', '1st', 'double', 2, true, 45000], ['104', '1st', 'single', 1, true, 60000],
                ['201', '2nd', 'double', 2, true, 45000], ['202', '2nd', 'single', 1, true, 60000],
            ];

            $pool = $students->where('gender', $gender)->filter(fn ($s) => $s->class_id)->values();
            $next = 0;
            $capacity = 0;
            foreach ($layout as $i => [$no, $floor, $kind, $cap, $ac, $fee]) {
                $maintenance = $no === '202';
                $capacity += $cap;
                $fill = $maintenance ? 0 : ($i % 4 === 3 ? max(0, $cap - 1) : $cap);
                $fill = min($fill, max(0, $pool->count() - $next));

                $roomId = $this->putGetId('hostel_rooms', [
                    'school_id' => $this->sid, 'hostel_id' => $hostelId, 'room_no' => $no, 'floor' => $floor, 'type' => $kind,
                    'capacity' => $cap, 'occupied' => $fill, 'ac' => $ac, 'monthly_fee' => $fee,
                    'status' => $maintenance ? 'maintenance' : ($fill >= $cap ? 'full' : 'available'),
                ]);

                $beds = [];
                for ($b = 1; $b <= $fill; $b++) {
                    $s = $pool[$next++];
                    $beds[] = [
                        'school_id' => $this->sid, 'hostel_id' => $hostelId, 'room_id' => $roomId, 'student_id' => $s->id,
                        'bed_no' => 'Bed ' . $b, 'joining_date' => $this->now->copy()->subDays(mt_rand(20, 40))->toDateString(),
                        'status' => 'active', 'fee_linked' => true, 'notes' => null,
                    ];
                }
                if ($beds) {
                    $this->put('hostel_allocations', $beds);
                }
            }

            DB::table('hostels')->where('id', $hostelId)->update(['total_rooms' => count($layout), 'total_capacity' => $capacity]);
        }
    }

    // ───────────────────────── school buses ─────────────────────────

    private function transport($students): void
    {
        $fleet = [
            ['Bus 1', 'Toyota Coaster', 'LSR 214 XA', 'bus', 32, 'Mr Sunday Okonkwo', 'Mrs Bisi Adeleke', [6.4698, 3.5852], 'Lekki / Ajah'],
            ['Bus 2', 'Toyota Hiace', 'KJA 508 EF', 'minibus', 18, 'Mr Rasheed Lawal', 'Mrs Grace Eze', [6.5095, 3.3711], 'Yaba / Surulere'],
            ['Bus 3', 'Toyota Coaster', 'APP 377 GH', 'bus', 32, 'Mr Emeka Nwosu', 'Mr Tunde Bakare', [6.6018, 3.3515], 'Ikeja / Maryland'],
            ['Bus 4', 'Toyota Hiace', 'EKY 912 AB', 'minibus', 18, 'Mr Ibrahim Danjuma', 'Mrs Funke Ojo', [6.6194, 3.5105], 'Ikorodu / Ketu'],
        ];
        $routes = [
            ['Lekki to School (Morning)', 'Ajah Roundabout', 'Heritage Crown Academy', 28000, [['Ajah Roundabout', '6:10 am'], ['Sangotedo Shoprite', '6:25 am'], ['Lekki Phase 1 Gate', '6:50 am'], ['Admiralty Way', '7:05 am']]],
            ['Yaba and Surulere', 'Bode Thomas Street', 'Heritage Crown Academy', 24000, [['Bode Thomas Street', '6:20 am'], ['Adeniran Ogunsanya', '6:35 am'], ['Jibowu Bus Stop', '6:55 am'], ['Herbert Macaulay Way', '7:10 am']]],
            ['Ikeja and Maryland', 'Opebi Link Road', 'Heritage Crown Academy', 22000, [['Opebi Link Road', '6:30 am'], ['Maryland Mall', '6:45 am'], ['Anthony Village', '7:00 am'], ['Gbagada Phase 1', '7:15 am']]],
            ['Ikorodu and Ketu', 'Ikorodu Garage', 'Heritage Crown Academy', 26000, [['Ikorodu Garage', '5:50 am'], ['Ketu Bus Stop', '6:30 am'], ['Ojota Interchange', '6:50 am'], ['Gbagada Expressway', '7:10 am']]],
        ];

        $pool = $students->shuffle()->values();
        $next = 0;
        foreach ($fleet as $i => [$name, $model, $reg, $type, $cap, $driver, $helper, $pos, $area]) {
            $vehicleId = $this->putGetId('vehicles', [
                'school_id' => $this->sid, 'registration_no' => $reg, 'name' => "{$name} ({$model})", 'type' => $type, 'capacity' => $cap,
                'driver_name' => $driver, 'driver_phone' => $this->phone(), 'helper_name' => $helper,
                'last_lat' => $pos[0], 'last_lng' => $pos[1], 'last_location_at' => $this->now->copy()->subMinutes(mt_rand(1, 12))->toDateTimeString(),
                'status' => 'active', 'tracking_token' => Str::random(40),
            ]);
            [$rname, $start, $end, $fee, $stops] = $routes[$i];
            $routeId = $this->putGetId('routes', [
                'school_id' => $this->sid, 'vehicle_id' => $vehicleId, 'name' => $rname, 'start_point' => $start, 'end_point' => $end,
                'stops' => json_encode(array_map(fn ($s) => ['name' => $s[0], 'pickup_time' => $s[1]], $stops)),
                'monthly_fee' => $fee, 'is_active' => true,
            ]);
            $riders = [];
            foreach (range(1, min($cap - 4, 14 + $i * 2)) as $_) {
                if (! isset($pool[$next])) {
                    break;
                }
                $riders[] = ['student_id' => $pool[$next++]->id, 'route_id' => $routeId, 'stop' => $this->pick($stops)[0], 'fee_linked' => false];
            }
            if ($riders) {
                $this->put('student_route', $riders);
            }
        }
    }

    // ───────────────────────── library loans ─────────────────────────

    private function libraryLoans($students, $staff): void
    {
        $books = DB::table('books')->where('school_id', $this->sid)->pluck('id');
        if ($books->isEmpty()) {
            return;
        }
        $rows = [];
        $today = $this->now->copy()->startOfDay();
        foreach (range(1, 34) as $n) {
            $isStaff = $n % 9 === 0 && $staff->isNotEmpty();
            $member = $isStaff ? $staff->random() : $students->random();
            $issued = $today->copy()->subDays(mt_rand(1, 40));
            $due = $issued->copy()->addDays(14);
            $returned = mt_rand(0, 100) < 55 ? $issued->copy()->addDays(mt_rand(3, 20)) : null;
            $perDay = 20.0;

            if ($returned) {
                $late = max(0, (int) $due->diffInDays($returned, false));
                $status = 'returned';
                $fine = $late * $perDay;
            } elseif ($due->lt($today)) {
                $status = 'overdue';
                $fine = (int) $due->diffInDays($today) * $perDay;
            } else {
                $status = 'issued';
                $fine = 0;
            }

            $rows[] = [
                'school_id' => $this->sid, 'book_id' => $books->random(),
                'member_type' => $isStaff ? 'App\\Models\\Staff' : 'App\\Models\\Student', 'member_id' => $member->id,
                'issued_date' => $issued->toDateString(), 'due_date' => $due->toDateString(),
                'returned_date' => $returned?->toDateString(), 'fine' => $fine, 'fine_per_day' => $perDay,
                'status' => $status, 'note' => null,
            ];
        }
        $this->put('book_issues', $rows);
    }

    // ───────────────────────── store ─────────────────────────

    private function store($staff): void
    {
        $catalog = [
            'Stationery' => [
                ['A4 Printing Paper (Ream)', 'ream', 40, 15, 5200], ['Marker Pens (Box of 12)', 'box', 6, 8, 3800], ['Chalk (Box)', 'box', 18, 10, 1500],
                ['Exercise Books (50 leaves)', 'pcs', 220, 200, 350], ['Whiteboard Eraser', 'pcs', 4, 6, 800],
            ],
            'Cleaning Supplies' => [
                ['Liquid Soap (5 litre)', 'litre', 9, 10, 4500], ['Disinfectant (Izal, 5 litre)', 'litre', 14, 6, 6200], ['Brooms', 'pcs', 3, 8, 1200], ['Toilet Tissue (Pack of 10)', 'pack', 25, 12, 2800],
            ],
            'Laboratory' => [
                ['Beakers (250 ml)', 'pcs', 36, 12, 1800], ['Test Tubes (Pack of 20)', 'pack', 5, 6, 3200], ['Bunsen Burner Gas Cylinder', 'pcs', 2, 3, 28000], ['Litmus Paper (Booklet)', 'pcs', 30, 10, 900],
            ],
            'Sports' => [
                ['Footballs (Size 5)', 'pcs', 7, 6, 9500], ['Whistles', 'pcs', 12, 4, 600], ['House Colour Bibs (Set of 12)', 'set', 8, 4, 7500],
            ],
            'Kitchen and Boarding' => [
                ['Rice (50 kg bag)', 'bag', 5, 6, 92000], ['Cooking Oil (25 litre)', 'jerrycan', 3, 4, 58000], ['Mattress Covers', 'pcs', 48, 20, 4500],
            ],
        ];
        $vendors = ['Ikeja Stationery Hub', 'Balogun Wholesale Mart', 'Mushin Cleaning Depot', 'Lagos Lab Supplies Ltd', 'Mile 12 Foodstuffs', 'Sportsworld Surulere'];

        foreach ($catalog as $category => $items) {
            $catId = $this->putGetId('inventory_categories', ['school_id' => $this->sid, 'name' => $category, 'description' => null]);
            foreach ($items as [$name, $unit, $stock, $min, $price]) {
                $itemId = $this->putGetId('inventory_items', [
                    'school_id' => $this->sid, 'category_id' => $catId, 'name' => $name, 'unit' => $unit,
                    'current_stock' => $stock, 'minimum_stock' => $min, 'description' => null, 'is_active' => true,
                ]);
                $qty = mt_rand(10, 60);
                $this->put('inventory_purchases', [[
                    'school_id' => $this->sid, 'item_id' => $itemId, 'vendor' => $this->pick($vendors),
                    'purchase_date' => $this->now->copy()->subDays(mt_rand(5, 60))->toDateString(), 'quantity' => $qty,
                    'unit_price' => $price, 'total_price' => $qty * $price, 'invoice_no' => 'INV-' . mt_rand(10000, 99999), 'notes' => null,
                ]]);
                if (mt_rand(0, 100) < 55 && $staff->isNotEmpty()) {
                    $who = $staff->random();
                    $this->put('inventory_issues', [[
                        'school_id' => $this->sid, 'item_id' => $itemId, 'issued_to_type' => 'staff', 'issued_to_id' => $who->id,
                        'issued_to_name' => $who->first_name . ' ' . $who->last_name, 'quantity' => mt_rand(1, 5),
                        'issue_date' => $this->now->copy()->subDays(mt_rand(1, 20))->toDateString(), 'return_date' => null,
                        'purpose' => $this->pick(['Classroom use', 'Practical class', 'Sports Day', 'Office use', 'Term supply']),
                        'status' => 'issued', 'notes' => null,
                    ]]);
                }
            }
        }
    }

    private function assets(): void
    {
        $rows = [
            ['Mikano 100 KVA Generator', 'Electronics', '2022-03-10', 9800000, 'Generator House', 'Estate Manager', 10],
            ['Epson Projector EB-X51', 'Electronics', '2023-09-04', 420000, 'Main Hall', 'ICT Department', 20],
            ['HP Desktop Computers (30 units)', 'Electronics', '2024-01-15', 12600000, 'ICT Laboratory', 'ICT Department', 20],
            ['Toyota Coaster School Bus', 'Vehicle', '2021-07-22', 28500000, 'Car Park', 'Transport Unit', 15],
            ['Science Laboratory Benches', 'Furniture', '2020-08-18', 1850000, 'Science Block', 'Chemistry Department', 10],
            ['Staff Room Tables and Chairs', 'Furniture', '2023-01-09', 960000, 'Staff Room', 'Admin Office', 10],
            ['Inverter and Battery Bank (10 KVA)', 'Electronics', '2025-02-12', 3400000, 'Admin Block', 'Bursary', 15],
            ['Borehole Water Pump', 'Other', '2022-11-05', 750000, 'Water Plant', 'Estate Manager', 10],
            ['Piano (Yamaha U1)', 'Other', '2019-05-30', 2300000, 'Music Room', 'Music Teacher', 8],
            ['CCTV System (16 cameras)', 'Electronics', '2024-06-20', 1450000, 'Security Post', 'Head of Security', 15],
        ];
        $out = [];
        foreach ($rows as $i => [$name, $cat, $date, $price, $where, $who, $rate]) {
            $years = Carbon::parse($date)->diffInYears($this->now);
            $out[] = [
                'school_id' => $this->sid, 'name' => $name, 'asset_code' => sprintf('AST-2026-%04d', $i + 1), 'category' => $cat,
                'purchase_date' => $date, 'purchase_price' => $price, 'current_value' => round($price * max(0.2, 1 - ($rate / 100) * $years)),
                'depreciation_method' => 'straight_line', 'depreciation_rate' => $rate, 'location' => $where, 'assigned_to' => $who,
                'status' => $i === 7 ? 'maintenance' : 'active', 'description' => null,
            ];
        }
        $this->put('assets', $out);
    }

    // ───────────────────────── front desk ─────────────────────────

    private function frontDesk(): void
    {
        $visitors = [
            ['Mrs Folake Adeyinka', 'Parent', 'Principal'], ['Mr Chidi Okeke', 'Fee enquiry', 'Bursar'], ['Alhaji Musa Bello', 'Meet class teacher', 'Mrs Adebisi (JSS 1)'],
            ['Engr. Tunde Ogunlana', 'Generator service', 'Estate Manager'], ['Mrs Ngozi Eze', 'Collect report card', 'Admin Office'], ['Mr Kunle Salami', 'Book supplier', 'Librarian'],
            ['Dr Amaka Obi', 'Admission enquiry', 'Front Desk'], ['Mr Yusuf Danjuma', 'Pick up child (sick bay)', 'School Nurse'], ['Mrs Bukola Thompson', 'PTA matter', 'Principal'],
            ['Mr Emeka Nwosu', 'Delivery: stationery', 'Store Keeper'], ['Pastor Idowu Ajayi', 'Chapel programme', 'Vice Principal'], ['Mrs Halima Garba', 'Transfer letter', 'Admin Office'],
            ['Mr Segun Alabi', 'Meet house master', 'Mr Bakare (Sports)'], ['Mrs Ifeoma Chukwu', 'Parent', 'Class teacher'],
        ];
        $rows = [];
        foreach ($visitors as $i => [$name, $purpose, $meet]) {
            $today = $i < 6;
            $in = $today
                ? $this->now->copy()->subMinutes(20 + $i * 35)
                : $this->now->copy()->subDays(1 + intdiv($i, 3))->setTime(8 + $i % 5, mt_rand(0, 55));
            $stillInside = $today && $i < 2;
            $rows[] = [
                'school_id' => $this->sid, 'name' => $name, 'phone' => $this->phone(), 'purpose' => $purpose, 'person_to_meet' => $meet,
                'time_in' => $in->toDateTimeString(), 'time_out' => $stillInside ? null : $in->copy()->addMinutes(mt_rand(10, 50))->toDateTimeString(),
                'remarks' => null,
            ];
        }
        $this->put('visitor_logs', $rows);

        $desk = DemoSchool::userFor('receptionist');
        $classes = ['Nursery 2', 'Primary 1', 'Primary 4', 'JSS 1', 'JSS 2', 'SS 1'];
        $inquiries = [
            ['Ayomide Fashola', 'JSS 1', 'Mrs Titilayo Fashola', 'new', 'walk-in', 'Wants a tour of the boarding house.', 2],
            ['Chiamaka Obi', 'Primary 4', 'Dr Amaka Obi', 'follow_up', 'referral', 'Referred by a current parent. Asked about the Lekki bus.', 6],
            ['Ibrahim Bello', 'SS 1', 'Alhaji Musa Bello', 'follow_up', 'walk-in', 'Coming from another school; needs to see last term results.', 9],
            ['Oluwadamilare Thompson', 'Nursery 2', 'Mrs Bukola Thompson', 'accepted', 'online', 'Entrance interview passed. Waiting for school fees payment.', 14],
            ['Zainab Garba', 'JSS 2', 'Mrs Halima Garba', 'new', 'online', 'Filled the form on the website.', 1],
            ['Kelechi Chukwu', 'Primary 1', 'Mrs Ifeoma Chukwu', 'follow_up', 'walk-in', 'Waiting for birth certificate.', 11],
            ['Temiloluwa Adebisi', 'JSS 1', 'Mr Femi Adebisi', 'dropped', 'walk-in', 'Chose a school closer to home.', 25],
            ['Nnamdi Eze', 'Primary 4', 'Mrs Ngozi Eze', 'new', 'referral', null, 3],
            ['Fatima Lawal', 'SS 1', 'Mr Rasheed Lawal', 'follow_up', 'online', 'Scholarship questions.', 5],
            ['Daniel Okonkwo', 'Primary 1', 'Mr Sunday Okonkwo', 'new', 'walk-in', null, 0],
        ];
        foreach ($inquiries as [$child, $class, $guardian, $status, $source, $notes, $daysAgo]) {
            $id = $this->putGetId('admission_inquiries', [
                'school_id' => $this->sid, 'student_name' => $child, 'class_interested' => $class, 'guardian_name' => $guardian,
                'guardian_phone' => $this->phone(), 'guardian_email' => null, 'status' => $status, 'notes' => $notes,
                'next_followup_date' => $status === 'follow_up' ? $this->now->copy()->addDays(mt_rand(1, 6))->toDateString() : null,
                'source' => $source, 'converted_student_id' => null,
            ]);
            DB::table('admission_inquiries')->where('id', $id)->update(['created_at' => $this->now->copy()->subDays($daysAgo)->toDateTimeString()]);

            if ($desk && in_array($status, ['follow_up', 'accepted'], true)) {
                $this->put('inquiry_followups', [[
                    'inquiry_id' => $id, 'staff_id' => $desk->id, 'note' => 'Called the parent and explained the fees and the next step.',
                    'next_date' => $this->now->copy()->addDays(3)->toDateString(),
                ]]);
            }
            if (in_array($status, ['accepted', 'follow_up'], true) && $class !== 'Nursery 2') {
                $passed = $status === 'accepted';
                $this->put('admission_assessments', [[
                    'school_id' => $this->sid, 'inquiry_id' => $id, 'type' => 'exam',
                    'scheduled_at' => $this->now->copy()->subDays(max(1, $daysAgo - 3))->setTime(10, 0)->toDateTimeString(),
                    'venue' => 'Main Hall', 'score' => $passed ? 72 : null, 'max_score' => 100,
                    'outcome' => $passed ? 'passed' : 'pending', 'remarks' => null, 'recorded_by' => $desk?->id,
                ]]);
            }
        }
    }

    // ───────────────────────── notices ─────────────────────────

    /** Announcements aimed at one kind of account, shown in that account's bell. */
    private function roleNotices(): void
    {
        $author = DemoSchool::userFor('admin')?->id;
        $items = [
            ['driver', 'Bus inspection on Friday', 'All school buses must be at the car park by 7:30 am on Friday for the road-worthiness check. Please bring your driver\'s licence and the vehicle papers.', 2],
            ['driver', 'New pick-up time for the Lekki route', 'From Monday, the Lekki bus leaves Ajah Roundabout at 6:10 am because of the traffic on the Lekki-Epe Expressway.', 5],
            ['warden', 'Boarding students\' visiting day', 'Parents may visit boarders this Saturday between 10 am and 2 pm. Please keep the visitors\' book at the hostel gate up to date.', 1],
            ['warden', 'Mattress covers delivered', 'Forty-eight new mattress covers are in the store. Collect them with the store keeper and sign for them.', 4],
            ['store-manager', 'Term supply list', 'Please send the Bursar the list of items running low before Wednesday so the purchase order can be approved.', 1],
            ['store-manager', 'Generator diesel delivery', 'Diesel arrives on Thursday morning. Please be at the gate to count and sign.', 3],
            ['librarian', 'Overdue books reminder', 'Please remind students with overdue books that the fine is ₦20 per day. Report cards will not be released with books outstanding.', 2],
            ['librarian', 'New books arrived', 'The new set of WAEC literature texts has arrived from the supplier. Please shelve them under Literature.', 6],
            ['receptionist', 'Open Day next Saturday', 'We expect about 40 prospective parents on Open Day. Please prepare the visitors\' tags and the brochures.', 1],
            ['receptionist', 'Remember to sign visitors out', 'Please make sure every visitor is signed out before they leave the compound.', 5],
            ['accountant', 'Second instalment due', 'The second part of First Term fees is due on the 30th. Please prepare the reminder list for parents who have not paid.', 2],
            ['accountant', 'Bank reconciliation', 'Please send this week\'s Paystack and bank transfer statements to the Proprietor by Friday.', 4],
            ['teacher', 'CA1 scores deadline', 'Please enter and submit CA1 scores for your classes before Friday so heads of department can review them.', 1],
            ['teacher', 'Staff meeting', 'Staff meeting holds on Wednesday at 2:30 pm in the staff room. Attendance is compulsory.', 3],
            ['principal', 'Results waiting for approval', 'Several classes have submitted their First Term results and are waiting for your approval.', 0],
            ['principal', 'Visitation by the Ministry of Education', 'Officials from the Lagos State Ministry of Education will visit on the 20th. Please prepare the school records.', 7],
        ];
        $rows = array_map(fn ($a) => [
            'school_id' => $this->sid, 'author_id' => $author, 'title' => $a[1], 'body' => $a[2], 'audience' => 'role', 'class_id' => null,
            'target_role' => $a[0], 'is_pinned' => false, 'published_at' => $this->now->copy()->subDays($a[3])->setTime(9, 15)->toDateTimeString(),
        ], $items);

        // A note for parents and one for pupils
        foreach ([['parent', 'Pay school fees online', 'You can now pay fees from your phone with a card or bank transfer. Open Fees, then tap Pay on the invoice.', 2], ['student', 'CBT practice test', 'A practice test for Mathematics is open this week. Log in and try it from the CBT page.', 1]] as $a) {
            $rows[] = [
                'school_id' => $this->sid, 'author_id' => $author, 'title' => $a[1], 'body' => $a[2], 'audience' => 'role', 'class_id' => null,
                'target_role' => $a[0], 'is_pinned' => false, 'published_at' => $this->now->copy()->subDays($a[3])->setTime(10, 0)->toDateTimeString(),
            ];
        }
        $this->put('announcements', $rows);
    }

    /** Personal bell notifications for every demo account. */
    private function notifications(): void
    {
        $byKey = [
            'admin' => [['Results submitted', 'JSS 2 submitted First Term results for approval.', false], ['New admission inquiry', 'Zainab Garba applied for JSS 2 through the website.', false], ['Fees collected', '₦1,250,000 was received today through Paystack.', true]],
            'principal' => [['Results waiting', 'SS 1 and JSS 3 results are waiting for your approval.', false], ['Staff leave request', 'Mrs Adebisi asked for two days off next week.', false], ['Attendance alert', 'Attendance in Primary 3 dropped below 85% this week.', true]],
            'teacher' => [['Scores due', 'CA1 scores for your classes are due on Friday.', false], ['Homework handed in', '12 pupils handed in the Mathematics homework.', false], ['Timetable change', 'Your Period 3 class on Thursday moved to Period 5.', true]],
            'accountant' => [['Payment received', 'Mrs Fashola paid ₦185,000 for First Term fees.', false], ['Parts overdue', '14 invoices are behind on their payment parts.', false], ['Payment needs review', 'A Monnify transfer was ₦5,000 short and needs your check.', false]],
            'parent' => [['Report card ready', 'Your child\'s First Term report card is ready to download.', false], ['Fees reminder', 'The second part of the school fees is due on the 30th.', false], ['PTA meeting', 'PTA meeting holds on Saturday at 10:00 am in the school hall.', true]],
            'student' => [['New homework', 'Mathematics homework is due on Friday.', false], ['Test open', 'CBT practice test for Mathematics is open now.', false], ['Library', 'Please return "Things Fall Apart" by Friday.', true]],
            'librarian' => [['Books overdue', '9 books are past their due date.', false], ['Reservation', 'A student reserved "Purple Hibiscus".', false], ['New stock', 'The supplier delivered 24 new books.', true]],
            'receptionist' => [['Visitor waiting', 'Mrs Folake Adeyinka is waiting to see the Principal.', false], ['Follow-ups today', '3 admission inquiries need a call today.', false], ['Open Day', 'Open Day holds next Saturday.', true]],
            'driver' => [['Route change', 'The Lekki bus now leaves Ajah Roundabout at 6:10 am.', false], ['Inspection', 'Bus inspection on Friday at 7:30 am.', false], ['Tracking reminder', 'Please keep the bus tracker switched on during trips.', true]],
            'warden' => [['Room in maintenance', 'Room 202 in Moremi House needs a plumber.', false], ['Visiting day', 'Parents visit boarders this Saturday.', false], ['New boarder', 'A new student was placed in Azikiwe House.', true]],
            'store-manager' => [['Stock running low', 'Brooms, Marker Pens and Whiteboard Erasers are below the minimum.', false], ['Delivery due', 'Diesel arrives on Thursday.', false], ['Request approved', 'The Bursar approved the new chalk order.', true]],
        ];

        DB::table('school_notifications')->where('school_id', $this->sid)->delete();
        $rows = [];
        foreach ($byKey as $key => $items) {
            $user = DemoSchool::userFor($key);
            if (! $user) {
                continue;
            }
            foreach ($items as $i => [$title, $body, $read]) {
                $at = $this->now->copy()->subHours(2 + $i * 7);
                $rows[] = [
                    'school_id' => $this->sid, 'user_id' => $user->id, 'type' => 'demo', 'title' => $title, 'body' => $body, 'data' => null,
                    'channel' => 'in-app', 'read_at' => $read ? $at->copy()->addHour()->toDateTimeString() : null,
                    'created_at' => $at->toDateTimeString(), 'updated_at' => $at->toDateTimeString(),
                ];
            }
        }
        if ($rows) {
            DB::table('school_notifications')->insert($rows);
        }
    }
}
