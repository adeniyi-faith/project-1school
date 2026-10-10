<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = [
            'super-admin',
            'school-admin',
            'principal',
            'teacher',
            'accountant',
            'librarian',
            'receptionist',
            'student',
            'parent',
            'driver',
            'warden',
            'store-manager',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create super admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@genius-sms.test'],
            [
                'name'     => 'Super Admin',
                'password' => bcrypt('password'),
                'status'   => 'active',
            ]
        );

        $admin->assignRole('super-admin');
    }
}
