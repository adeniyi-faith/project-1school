<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayrollController extends Controller
{
    // ── Salary Structure ──────────────────────────────────────────

    public function structure(Request $request)
    {
        $sid = $this->getSchoolId();

        $staffList = Staff::with(['department:id,name', 'designation:id,name', 'salaryStructure'])
            ->where('school_id', $sid)
            ->where('status', 'active')
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/HR/SalaryStructure', [
            'staffList'   => $staffList,
            'departments' => Department::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'filters'     => $request->only('department_id'),
        ]);
    }

    public function saveStructure(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'allowances'   => 'nullable|array',
            'allowances.*.label'  => 'required|string|max:100',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions'   => 'nullable|array',
            'deductions.*.label'  => 'required|string|max:100',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);

        SalaryStructure::updateOrCreate(
            ['school_id' => $this->getSchoolId(), 'staff_id' => $staff->id],
            [
                'basic_salary' => $data['basic_salary'],
                'allowances'   => $data['allowances'] ?? [],
                'deductions'   => $data['deductions'] ?? [],
                'is_active'    => true,
            ]
        );

        return back()->with('success', 'Salary structure saved.');
    }

    // ── Payroll Generation ────────────────────────────────────────

    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $payrolls = Payroll::with(['staff:id,first_name,last_name,emp_id,department_id', 'staff.department:id,name'])
            ->when($request->month_year,    fn ($q) => $q->where('month_year', $request->month_year))
            ->when($request->department_id, fn ($q) => $q->whereHas('staff', fn ($sq) => $sq->where('department_id', $request->department_id)))
            ->when($request->status,        fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total_net'     => (float) Payroll::where('school_id', $sid)
                ->when($request->month_year, fn ($q) => $q->where('month_year', $request->month_year))
                ->sum('net_salary'),
            'paid_count'    => Payroll::where('school_id', $sid)->where('status', 'paid')
                ->when($request->month_year, fn ($q) => $q->where('month_year', $request->month_year))
                ->count(),
            'draft_count'   => Payroll::where('school_id', $sid)->where('status', 'draft')
                ->when($request->month_year, fn ($q) => $q->where('month_year', $request->month_year))
                ->count(),
        ];

        return Inertia::render('SchoolAdmin/HR/Payroll', [
            'payrolls'    => $payrolls,
            'departments' => Department::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'filters'     => $request->only('month_year', 'department_id', 'status'),
            'stats'       => $stats,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'month_year'    => 'required|string|regex:/^\d{4}-\d{2}$/',
            'department_id' => 'nullable|exists:departments,id',
            'working_days'  => 'required|integer|min:1|max:31',
        ]);

        $sid = $this->getSchoolId();
        $monthYear = $data['month_year'];

        $staff = Staff::with('salaryStructure')
            ->where('school_id', $sid)
            ->where('status', 'active')
            ->when($data['department_id'] ?? null, fn ($q) => $q->where('department_id', $data['department_id']))
            ->get();

        $generated = 0;
        $workingDays = (int) $data['working_days'];

        DB::transaction(function () use ($staff, $sid, $monthYear, $workingDays, &$generated) {
            foreach ($staff as $member) {
                $struct = $member->salaryStructure;
                if (!$struct) continue;

                // Check attendance for the month
                [$year, $month] = explode('-', $monthYear);
                $presentDays = Attendance::where('school_id', $sid)
                    ->where('attendable_type', 'App\\Models\\Staff')
                    ->where('attendable_id', $member->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->whereIn('status', ['present', 'late'])
                    ->count();

                if ($presentDays === 0) $presentDays = $workingDays; // fallback if no attendance data

                $perDaySalary = $struct->net_salary / $workingDays;
                $leaveDays    = max(0, $workingDays - $presentDays);
                $deduction    = $struct->total_deductions + ($leaveDays > 0 ? $perDaySalary * $leaveDays : 0);
                $netSalary    = round((float) $struct->basic_salary + $struct->total_allowances - $deduction, 2);

                Payroll::updateOrCreate(
                    ['school_id' => $sid, 'staff_id' => $member->id, 'month_year' => $monthYear],
                    [
                        'basic_salary'        => $struct->basic_salary,
                        'total_allowances'    => $struct->total_allowances,
                        'total_deductions'    => $struct->total_deductions,
                        'net_salary'          => max(0, $netSalary),
                        'working_days'        => $workingDays,
                        'present_days'        => $presentDays,
                        'leave_days'          => $leaveDays,
                        'allowances_snapshot' => $struct->allowances,
                        'deductions_snapshot' => $struct->deductions,
                        'status'              => 'generated',
                    ]
                );
                $generated++;
            }
        });

        return back()->with('success', "Payroll generated for {$generated} staff members.");
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        $payroll->update([
            'status'  => 'paid',
            'paid_on' => now()->toDateString(),
        ]);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function slip(Payroll $payroll)
    {
        $payroll->load(['staff:id,first_name,last_name,emp_id,department_id,designation_id,joining_date',
            'staff.department:id,name', 'staff.designation:id,name']);

        return Inertia::render('SchoolAdmin/HR/Payslip', [
            'payroll' => $payroll,
        ]);
    }
}
