<?php

namespace Database\Seeders;

use App\Models\User;
// Seeders imported via namespace autoloading
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RolePermissionSeeder::class,
            DemoUserSeeder::class,
            SchoolSetupSeeder::class,
            StudentSeeder::class,
            StaffSeeder::class,
            AttendanceSeeder::class,
            TimetableSeeder::class,
            ExamSeeder::class,
            FeeSeeder::class,
            HRSeeder::class,
            LibrarySeeder::class,
        ]);
    }
}
