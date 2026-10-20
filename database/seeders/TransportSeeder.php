<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class TransportSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) return;

        $sid = $school->id;

        // Vehicles
        $vehiclesData = [
            ['registration_no' => 'LAGOS-APP-123-XY', 'name' => 'Bus 01', 'type' => 'bus',     'capacity' => 45, 'driver_name' => 'Mr. Adewale',   'driver_phone' => '0803-111-1111'],
            ['registration_no' => 'LAGOS-APP-567-XY', 'name' => 'Bus 02', 'type' => 'bus',     'capacity' => 45, 'driver_name' => 'Mr. Okonkwo',   'driver_phone' => '0803-222-2222'],
            ['registration_no' => 'LAGOS-APP-901-XY', 'name' => 'Mini 01','type' => 'minibus', 'capacity' => 20, 'driver_name' => 'Mr. Bello',   'driver_phone' => '0803-333-3333'],
        ];

        $vehicles = [];
        foreach ($vehiclesData as $vd) {
            $vehicles[] = Vehicle::firstOrCreate(
                ['school_id' => $sid, 'registration_no' => $vd['registration_no']],
                array_merge($vd, ['school_id' => $sid])
            );
        }

        // Routes
        $routesData = [
            [
                'name'        => 'Lekki Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Lekki Phase 1',
                'monthly_fee' => 800,
                'vehicle_idx' => 0,
                'stops'       => [
                    ['name' => 'Ajah', 'pickup_time' => '07:15'],
                    ['name' => 'Chevron', 'pickup_time' => '07:25'],
                    ['name' => 'Lekki Phase 1','pickup_time' => '07:35'],
                ],
            ],
            [
                'name'        => 'Surulere Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Surulere Stadium',
                'monthly_fee' => 700,
                'vehicle_idx' => 1,
                'stops'       => [
                    ['name' => 'Yaba', 'pickup_time' => '07:20'],
                    ['name' => 'Ojuelegba','pickup_time' => '07:30'],
                    ['name' => 'Surulere Stadium','pickup_time' => '07:40'],
                ],
            ],
            [
                'name'        => 'Ikeja Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Ikeja GRA',
                'monthly_fee' => 1000,
                'vehicle_idx' => 2,
                'stops'       => [
                    ['name' => 'Allen Avenue', 'pickup_time' => '06:50'],
                    ['name' => 'Computer Village', 'pickup_time' => '07:00'],
                    ['name' => 'Ikeja GRA', 'pickup_time' => '07:10'],
                ],
            ],
        ];

        foreach ($routesData as $rd) {
            $vehicleId = $vehicles[$rd['vehicle_idx']]->id ?? null;
            $route = TransportRoute::firstOrCreate(
                ['school_id' => $sid, 'name' => $rd['name']],
                [
                    'school_id'   => $sid,
                    'vehicle_id'  => $vehicleId,
                    'start_point' => $rd['start_point'],
                    'end_point'   => $rd['end_point'],
                    'monthly_fee' => $rd['monthly_fee'],
                    'stops'       => $rd['stops'],
                ]
            );

            // Assign a few students to each route
            $students = Student::where('school_id', $sid)->inRandomOrder()->limit(5)->get();
            foreach ($students as $student) {
                try {
                    $route->students()->syncWithoutDetaching([
                        $student->id => ['stop' => $rd['stops'][0]['name'], 'fee_linked' => true],
                    ]);
                } catch (\Exception $e) {
                    // skip duplicates
                }
            }
        }
    }
}
