<?php

namespace Database\Seeders;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Seeder;

class HostelSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) return;

        $sid = $school->id;

        $hostelsData = [
            ['name' => 'Boys Hostel Block A', 'type' => 'boys',  'address' => 'East Wing, School Campus'],
            ['name' => 'Girls Hostel Block B', 'type' => 'girls', 'address' => 'West Wing, School Campus'],
        ];

        foreach ($hostelsData as $hd) {
            $hostel = Hostel::firstOrCreate(
                ['school_id' => $sid, 'name' => $hd['name']],
                array_merge($hd, ['school_id' => $sid, 'status' => 'active'])
            );

            $roomsData = [
                ['room_no' => '101', 'floor' => 'Ground', 'type' => 'double',    'capacity' => 2, 'ac' => false, 'monthly_fee' => 2500],
                ['room_no' => '102', 'floor' => 'Ground', 'type' => 'double',    'capacity' => 2, 'ac' => false, 'monthly_fee' => 2500],
                ['room_no' => '201', 'floor' => '1st',    'type' => 'dormitory', 'capacity' => 6, 'ac' => false, 'monthly_fee' => 1500],
                ['room_no' => '202', 'floor' => '1st',    'type' => 'single',    'capacity' => 1, 'ac' => true,  'monthly_fee' => 4000],
                ['room_no' => '203', 'floor' => '1st',    'type' => 'double',    'capacity' => 2, 'ac' => true,  'monthly_fee' => 3500],
            ];

            $totalCap = 0;
            $totalRooms = 0;

            foreach ($roomsData as $rd) {
                HostelRoom::firstOrCreate(
                    ['hostel_id' => $hostel->id, 'room_no' => $rd['room_no']],
                    array_merge($rd, ['school_id' => $sid, 'hostel_id' => $hostel->id])
                );
                $totalCap += $rd['capacity'];
                $totalRooms++;
            }

            $hostel->update(['total_rooms' => $totalRooms, 'total_capacity' => $totalCap]);

            // Assign a few students
            $students = Student::where('school_id', $sid)->inRandomOrder()->limit(4)->get();
            $rooms    = HostelRoom::where('hostel_id', $hostel->id)->where('type', 'dormitory')->get();

            foreach ($students as $idx => $student) {
                $existing = HostelAllocation::where('student_id', $student->id)->where('status', 'active')->exists();
                if ($existing) continue;

                $room = $rooms->first();
                if (! $room || $room->occupied >= $room->capacity) continue;

                HostelAllocation::create([
                    'school_id'    => $sid,
                    'hostel_id'    => $hostel->id,
                    'room_id'      => $room->id,
                    'student_id'   => $student->id,
                    'bed_no'       => 'B' . ($room->occupied + 1),
                    'joining_date' => now()->subMonths(rand(1, 6))->toDateString(),
                    'status'       => 'active',
                    'fee_linked'   => true,
                ]);
                $room->increment('occupied');
            }
        }
    }
}
