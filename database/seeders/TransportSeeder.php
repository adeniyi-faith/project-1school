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
            ['registration_no' => 'DHAKA-METRO-GA-1234', 'name' => 'Bus 01', 'type' => 'bus',     'capacity' => 45, 'driver_name' => 'Md. Rahim',   'driver_phone' => '01711-111111'],
            ['registration_no' => 'DHAKA-METRO-GA-5678', 'name' => 'Bus 02', 'type' => 'bus',     'capacity' => 45, 'driver_name' => 'Md. Karim',   'driver_phone' => '01711-222222'],
            ['registration_no' => 'DHAKA-METRO-GA-9012', 'name' => 'Mini 01','type' => 'minibus', 'capacity' => 20, 'driver_name' => 'Md. Salam',   'driver_phone' => '01711-333333'],
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
                'name'        => 'Mirpur Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Mirpur-10',
                'monthly_fee' => 800,
                'vehicle_idx' => 0,
                'stops'       => [
                    ['name' => 'Mirpur-1', 'pickup_time' => '07:15'],
                    ['name' => 'Mirpur-6', 'pickup_time' => '07:25'],
                    ['name' => 'Mirpur-10','pickup_time' => '07:35'],
                ],
            ],
            [
                'name'        => 'Dhanmondi Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Dhanmondi-27',
                'monthly_fee' => 700,
                'vehicle_idx' => 1,
                'stops'       => [
                    ['name' => 'Dhanmondi-2', 'pickup_time' => '07:20'],
                    ['name' => 'Dhanmondi-15','pickup_time' => '07:30'],
                    ['name' => 'Dhanmondi-27','pickup_time' => '07:40'],
                ],
            ],
            [
                'name'        => 'Uttara Route',
                'start_point' => 'School Gate',
                'end_point'   => 'Uttara Sector-7',
                'monthly_fee' => 1000,
                'vehicle_idx' => 2,
                'stops'       => [
                    ['name' => 'Uttara Sector-3', 'pickup_time' => '06:50'],
                    ['name' => 'Uttara Sector-5', 'pickup_time' => '07:00'],
                    ['name' => 'Uttara Sector-7', 'pickup_time' => '07:10'],
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
