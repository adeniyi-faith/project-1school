<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\School;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class HRSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        // ── Leave Types ───────────────────────────────────────────────
        $leaveTypeData = [
            ['name' => 'Annual Leave',      'max_days_per_year' => 20, 'is_paid' => true,  'description' => 'Yearly paid annual leave'],
            ['name' => 'Sick Leave',         'max_days_per_year' => 14, 'is_paid' => true,  'description' => 'Medical/health related leave'],
            ['name' => 'Casual Leave',       'max_days_per_year' => 10, 'is_paid' => true,  'description' => 'Short-notice personal leave'],
            ['name' => 'Maternity Leave',    'max_days_per_year' => 90, 'is_paid' => true,  'description' => 'Maternity leave for female staff'],
            ['name' => 'Unpaid Leave',       'max_days_per_year' => 30, 'is_paid' => false, 'description' => 'Leave without pay'],
        ];

        $leaveTypes = [];
        foreach ($leaveTypeData as $lt) {
            $leaveTypes[] = LeaveType::firstOrCreate(
                ['school_id' => $sid, 'name' => $lt['name']],
                array_merge($lt, ['is_active' => true])
            );
        }

        // ── Salary Structures ─────────────────────────────────────────
        $staff = Staff::where('school_id', $sid)->where('status', 'active')->get();

        $allowancePool = [
            ['label' => 'House Rent Allowance', 'amount' => 3000],
            ['label' => 'Medical Allowance',     'amount' => 1000],
            ['label' => 'Transport Allowance',   'amount' => 800],
        ];
        $deductionPool = [
            ['label' => 'Income Tax',   'amount' => 500],
            ['label' => 'Provident Fund','amount' => 1000],
        ];

        // Salary tiers by index
        $salaryTiers = [15000, 18000, 20000, 22000, 25000, 28000, 30000, 35000];

        foreach ($staff as $i => $member) {
            $basic = $salaryTiers[$i % count($salaryTiers)];

            SalaryStructure::firstOrCreate(
                ['school_id' => $sid, 'staff_id' => $member->id],
                [
                    'basic_salary' => $basic,
                    'allowances'   => $allowancePool,
                    'deductions'   => $deductionPool,
                    'is_active'    => true,
                ]
            );
        }

        // ── Leave Requests (sample) ───────────────────────────────────
        $sampleStaff = $staff->take(6);
        $statuses = ['pending', 'approved', 'approved', 'rejected', 'approved', 'pending'];

        foreach ($sampleStaff as $i => $member) {
            $type = $leaveTypes[$i % count($leaveTypes)];
            $start = now()->subDays(rand(5, 30))->toDateString();
            $end   = now()->subDays(rand(1, 4))->toDateString();
            if ($end < $start) $end = $start;

            $exists = LeaveRequest::where('staff_id', $member->id)->where('start_date', $start)->exists();
            if ($exists) continue;

            LeaveRequest::create([
                'school_id'     => $sid,
                'staff_id'      => $member->id,
                'leave_type_id' => $type->id,
                'start_date'    => $start,
                'end_date'      => $end,
                'days'          => 1,
                'reason'        => 'Personal reason.',
                'status'        => $statuses[$i % count($statuses)],
            ]);
        }

        // ── Payroll (March 2026) ──────────────────────────────────────
        foreach ($staff->take(10) as $i => $member) {
            $struct = $member->salaryStructure ?? SalaryStructure::where('staff_id', $member->id)->first();
            if (!$struct) continue;

            $allowances = collect($struct->allowances ?? [])->sum('amount');
            $deductions = collect($struct->deductions ?? [])->sum('amount');
            $net        = (float)$struct->basic_salary + $allowances - $deductions;

            Payroll::firstOrCreate(
                ['school_id' => $sid, 'staff_id' => $member->id, 'month_year' => '2026-03'],
                [
                    'basic_salary'        => $struct->basic_salary,
                    'total_allowances'    => $allowances,
                    'total_deductions'    => $deductions,
                    'net_salary'          => max(0, $net),
                    'working_days'        => 26,
                    'present_days'        => rand(22, 26),
                    'leave_days'          => rand(0, 4),
                    'allowances_snapshot' => $struct->allowances,
                    'deductions_snapshot' => $struct->deductions,
                    'status'              => $i < 6 ? 'paid' : 'generated',
                    'paid_on'             => $i < 6 ? '2026-03-30' : null,
                ]
            );
        }

        $this->command->info('HR seeded: ' . count($leaveTypeData) . ' leave types, ' . $staff->count() . ' salary structures, 10 payroll records.');
    }
}
