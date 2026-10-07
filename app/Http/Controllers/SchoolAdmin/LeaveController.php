<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaveController extends Controller
{
    // ── Leave Types ───────────────────────────────────────────────

    public function types()
    {
        $sid = $this->getSchoolId();

        return Inertia::render('SchoolAdmin/HR/LeaveTypes', [
            'types' => LeaveType::where('school_id', $sid)
                ->withCount('leaveRequests')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'max_days_per_year' => 'required|integer|min:0|max:365',
            'is_paid'           => 'boolean',
            'description'       => 'nullable|string|max:255',
            'is_active'         => 'boolean',
        ]);

        LeaveType::create(array_merge($data, [
            'school_id' => $this->getSchoolId(),
            'is_paid'   => $data['is_paid'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]));

        return back()->with('success', 'Leave type created.');
    }

    public function updateType(Request $request, LeaveType $leaveType)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'max_days_per_year' => 'required|integer|min:0|max:365',
            'is_paid'           => 'boolean',
            'description'       => 'nullable|string|max:255',
            'is_active'         => 'boolean',
        ]);

        $leaveType->update($data);
        return back()->with('success', 'Leave type updated.');
    }

    public function destroyType(LeaveType $leaveType)
    {
        $leaveType->delete();
        return back()->with('success', 'Leave type deleted.');
    }

    // ── Leave Requests ────────────────────────────────────────────

    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $requests = LeaveRequest::with([
            'staff:id,first_name,last_name,emp_id,department_id',
            'staff.department:id,name',
            'leaveType:id,name,is_paid',
            'approvedBy:id,name',
        ])
            ->when($request->status,      fn ($q) => $q->where('status', $request->status))
            ->when($request->staff_id,    fn ($q) => $q->where('staff_id', $request->staff_id))
            ->when($request->department_id, fn ($q) => $q->whereHas('staff', fn ($sq) => $sq->where('department_id', $request->department_id)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'pending'  => LeaveRequest::where('school_id', $sid)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('school_id', $sid)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('school_id', $sid)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('SchoolAdmin/HR/Leaves', [
            'requests'    => $requests,
            'leaveTypes'  => LeaveType::where('school_id', $sid)->where('is_active', true)->get(['id', 'name']),
            'staffList'   => Staff::where('school_id', $sid)->where('status', 'active')
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'emp_id']),
            'filters'     => $request->only('status', 'staff_id', 'department_id'),
            'stats'       => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'staff_id'      => 'required|exists:staff,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:500',
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end   = \Carbon\Carbon::parse($data['end_date']);
        $days  = $start->diffInWeekdays($end) + 1;

        LeaveRequest::create(array_merge($data, [
            'school_id' => $this->getSchoolId(),
            'days'      => $days,
            'status'    => 'pending',
        ]));

        return back()->with('success', 'Leave request submitted.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        abort_if($leaveRequest->school_id !== $this->getSchoolId(), 403);

        $data = $request->validate([
            'action'        => 'required|in:approved,rejected',
            'approval_note' => 'nullable|string|max:500',
        ]);

        $leaveRequest->update([
            'status'        => $data['action'],
            'approved_by'   => auth()->id(),
            'approval_note' => $data['approval_note'] ?? null,
            'actioned_at'   => now(),
        ]);

        return back()->with('success', 'Leave request ' . $data['action'] . '.');
    }
}
