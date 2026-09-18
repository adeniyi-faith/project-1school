<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    private array $permissions = [
        // Schools (super-admin only)
        'schools.view', 'schools.create', 'schools.edit', 'schools.delete', 'schools.suspend',

        // Academic Years
        'academic-years.view', 'academic-years.create', 'academic-years.edit', 'academic-years.delete',

        // Users
        'users.view', 'users.create', 'users.edit', 'users.delete', 'users.impersonate',

        // Students
        'students.view', 'students.create', 'students.edit', 'students.delete',
        'students.import', 'students.export', 'students.promote', 'students.idcard',

        // Staff
        'staff.view', 'staff.create', 'staff.edit', 'staff.delete',

        // Attendance
        'attendance.view', 'attendance.mark', 'attendance.report', 'attendance.export',

        // Timetable
        'timetable.view', 'timetable.manage',

        // Examinations
        'exams.view', 'exams.create', 'exams.edit', 'exams.delete',
        'marks.view', 'marks.entry', 'marks.import',
        'results.view', 'results.publish', 'results.lock',
        'reportcard.generate',

        // Fees
        'fees.view', 'fees.collect', 'fees.structure', 'fees.reports', 'fees.waiver',
        'fees.online', 'expenses.view', 'expenses.create',

        // Payroll & HR
        'payroll.view', 'payroll.generate', 'payslip.download',
        'leave.view', 'leave.apply', 'leave.approve',
        'staff.performance',

        // Library
        'library.view', 'library.manage', 'library.issue', 'library.report',

        // Transport
        'transport.view', 'transport.manage', 'transport.attendance',

        // Hostel
        'hostel.view', 'hostel.manage', 'hostel.attendance',

        // Inventory
        'inventory.view', 'inventory.manage', 'inventory.issue',

        // Homework & Lessons
        'homework.view', 'homework.create', 'homework.grade',
        'lessons.view', 'lessons.create', 'lessons.approve',
        'syllabus.view', 'syllabus.manage',

        // Communication
        'announcements.view', 'announcements.create', 'announcements.delete',
        'messages.view', 'messages.send',
        'sms.send', 'email.send',

        // Reports
        'reports.view', 'reports.export', 'reports.custom',

        // Settings
        'settings.view', 'settings.edit',
        'roles.view', 'roles.manage',
    ];

    private array $rolePermissions = [
        'super-admin' => '*',

        'school-admin' => [
            'academic-years.*', 'users.view', 'users.create', 'users.edit',
            'students.*', 'staff.*',
            'attendance.*', 'timetable.*',
            'exams.*', 'marks.*', 'results.*', 'reportcard.*',
            'fees.*', 'expenses.*',
            'payroll.*', 'payslip.*', 'leave.*', 'staff.performance',
            'library.*', 'transport.*', 'hostel.*', 'inventory.*',
            'homework.*', 'lessons.*', 'syllabus.*',
            'announcements.*', 'messages.*', 'sms.*', 'email.*',
            'reports.*', 'settings.*',
        ],

        'principal' => [
            'academic-years.view', 'users.view',
            'students.view', 'students.export', 'students.idcard',
            'staff.view',
            'attendance.view', 'attendance.report', 'attendance.export',
            'timetable.view',
            'exams.view', 'marks.view', 'results.view', 'results.publish', 'results.lock', 'reportcard.generate',
            'fees.view', 'fees.reports',
            'leave.view', 'leave.approve',
            'homework.view', 'lessons.view', 'lessons.approve', 'syllabus.view',
            'announcements.view', 'announcements.create', 'messages.view', 'messages.send',
            'reports.view', 'reports.export',
        ],

        'teacher' => [
            'students.view',
            'attendance.view', 'attendance.mark',
            'timetable.view',
            'exams.view', 'marks.view', 'marks.entry',
            'results.view', 'reportcard.generate',
            'homework.view', 'homework.create', 'homework.grade',
            'lessons.view', 'lessons.create', 'syllabus.view',
            'announcements.view', 'messages.view', 'messages.send',
            'reports.view',
        ],

        'accountant' => [
            'students.view',
            'fees.view', 'fees.collect', 'fees.structure', 'fees.reports', 'fees.waiver', 'fees.online',
            'expenses.view', 'expenses.create',
            'payroll.view', 'payroll.generate', 'payslip.download',
            'reports.view', 'reports.export',
        ],

        'librarian' => [
            'students.view', 'staff.view',
            'library.view', 'library.manage', 'library.issue', 'library.report',
            'reports.view',
        ],

        'receptionist' => [
            'students.view', 'students.create',
            'announcements.view',
            'messages.view', 'messages.send',
        ],

        'driver' => [
            'transport.view', 'transport.attendance',
        ],

        'warden' => [
            'students.view',
            'hostel.view', 'hostel.attendance',
        ],

        'store-manager' => [
            'inventory.view', 'inventory.manage', 'inventory.issue',
        ],

        // student and parent access controlled via policies, not permissions
        'student'  => [],
        'parent'   => [],
    ];

    public function run(): void
    {
        // Create all permissions
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        foreach ($this->rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($perms === '*') {
                $role->syncPermissions(Permission::all());
                continue;
            }

            $resolved = [];
            foreach ($perms as $perm) {
                if (str_ends_with($perm, '.*')) {
                    $prefix = rtrim($perm, '.*');
                    $matched = Permission::where('name', 'like', $prefix . '.%')->pluck('name')->toArray();
                    $resolved = array_merge($resolved, $matched);
                } else {
                    $resolved[] = $perm;
                }
            }

            $role->syncPermissions(array_unique($resolved));
        }

        $this->command->info('Roles and permissions seeded.');
    }
}
