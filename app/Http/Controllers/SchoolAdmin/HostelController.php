<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HostelController extends Controller
{
    // ── Hostels ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $hostels = Hostel::with('warden:id,first_name,last_name')
            ->withCount(['rooms', 'allocations' => fn ($q) => $q->where('status', 'active')])
            ->where('school_id', $sid)
            ->orderBy('name')
            ->get();

        $stats = [
            'total_hostels'  => $hostels->count(),
            'total_capacity' => $hostels->sum('total_capacity'),
            'occupied'       => HostelAllocation::where('school_id', $sid)->where('status', 'active')->count(),
        ];

        return Inertia::render('SchoolAdmin/Hostel/Index', [
            'hostels'   => $hostels,
            'staffList' => Staff::where('school_id', $sid)->where('status', 'active')
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'emp_id']),
            'stats'     => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'type'       => 'required|in:boys,girls,mixed',
            'warden_id'  => 'nullable|exists:staff,id',
            'address'    => 'nullable|string',
            'status'     => 'required|in:active,inactive',
        ]);

        Hostel::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Hostel created.');
    }

    public function update(Request $request, Hostel $hostel)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:150',
            'type'       => 'required|in:boys,girls,mixed',
            'warden_id'  => 'nullable|exists:staff,id',
            'address'    => 'nullable|string',
            'status'     => 'required|in:active,inactive',
        ]);

        $hostel->update($data);
        return back()->with('success', 'Hostel updated.');
    }

    public function destroy(Hostel $hostel)
    {
        $hostel->delete();
        return back()->with('success', 'Hostel deleted.');
    }

    // ── Rooms ─────────────────────────────────────────────────────

    public function rooms(Request $request, Hostel $hostel)
    {
        $hostel->load('warden:id,first_name,last_name');

        $rooms = HostelRoom::withCount(['allocations' => fn ($q) => $q->where('status', 'active')])
            ->where('hostel_id', $hostel->id)
            ->orderBy('floor')
            ->orderBy('room_no')
            ->get();

        return Inertia::render('SchoolAdmin/Hostel/Rooms', [
            'hostel' => $hostel,
            'rooms'  => $rooms,
        ]);
    }

    public function storeRoom(Request $request, Hostel $hostel)
    {
        $data = $request->validate([
            'room_no'     => 'required|string|max:20',
            'floor'       => 'nullable|string|max:20',
            'type'        => 'required|in:single,double,dormitory',
            'capacity'    => 'required|integer|min:1|max:50',
            'ac'          => 'boolean',
            'monthly_fee' => 'nullable|numeric|min:0',
        ]);

        $data['school_id'] = $this->getSchoolId();
        $data['hostel_id'] = $hostel->id;
        $data['ac']        = $data['ac'] ?? false;

        HostelRoom::create($data);

        // Update hostel totals
        $hostel->increment('total_rooms');
        $hostel->increment('total_capacity', (int)$data['capacity']);

        return back()->with('success', 'Room added.');
    }

    public function updateRoom(Request $request, Hostel $hostel, HostelRoom $room)
    {
        $data = $request->validate([
            'room_no'     => 'required|string|max:20',
            'floor'       => 'nullable|string|max:20',
            'type'        => 'required|in:single,double,dormitory',
            'capacity'    => 'required|integer|min:1|max:50',
            'ac'          => 'boolean',
            'monthly_fee' => 'nullable|numeric|min:0',
            'status'      => 'required|in:available,full,maintenance',
        ]);

        $oldCapacity = $room->capacity;
        $room->update($data);

        // Recalculate hostel total_capacity
        $diff = (int)$data['capacity'] - $oldCapacity;
        if ($diff !== 0) {
            $hostel->increment('total_capacity', $diff);
        }

        return back()->with('success', 'Room updated.');
    }

    public function destroyRoom(Hostel $hostel, HostelRoom $room)
    {
        $hostel->decrement('total_rooms');
        $hostel->decrement('total_capacity', $room->capacity);
        $room->delete();
        return back()->with('success', 'Room deleted.');
    }

    // ── Allocations ───────────────────────────────────────────────

    public function allocations(Request $request)
    {
        $sid = $this->getSchoolId();

        $allocations = HostelAllocation::with([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'hostel:id,name,type',
            'room:id,room_no,floor,type',
        ])
            ->where('school_id', $sid)
            ->when($request->hostel_id, fn ($q) => $q->where('hostel_id', $request->hostel_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Hostel/Allocations', [
            'allocations' => $allocations,
            'hostels'     => Hostel::where('school_id', $sid)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name', 'type']),
            'filters'     => $request->only('hostel_id', 'status'),
        ]);
    }

    public function storeAllocation(Request $request)
    {
        $data = $request->validate([
            'hostel_id'    => 'required|exists:hostels,id',
            'room_id'      => 'required|exists:hostel_rooms,id',
            'student_id'   => 'required|exists:students,id',
            'bed_no'       => 'nullable|string|max:20',
            'joining_date' => 'required|date',
            'fee_linked'   => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $room = HostelRoom::findOrFail($data['room_id']);

        if ($room->occupied >= $room->capacity) {
            return back()->withErrors(['room_id' => 'Room is at full capacity.']);
        }

        // Check student not already active in another room
        $existing = HostelAllocation::where('student_id', $data['student_id'])
            ->where('status', 'active')->first();
        if ($existing) {
            return back()->withErrors(['student_id' => 'Student is already allocated to a hostel room.']);
        }

        $data['school_id'] = $this->getSchoolId();
        $data['status']    = 'active';

        DB::transaction(function () use ($data, $room) {
            HostelAllocation::create($data);
            $room->increment('occupied');
            if ($room->occupied >= $room->capacity) {
                $room->update(['status' => 'full']);
            }
        });

        return back()->with('success', 'Student allocated to hostel room.');
    }

    public function vacate(Request $request, HostelAllocation $allocation)
    {
        $data = $request->validate([
            'leaving_date' => 'required|date',
        ]);

        DB::transaction(function () use ($allocation, $data) {
            $allocation->update([
                'status'       => 'left',
                'leaving_date' => $data['leaving_date'],
            ]);

            $room = $allocation->room;
            $room->decrement('occupied');
            if ($room->status === 'full') {
                $room->update(['status' => 'available']);
            }
        });

        return back()->with('success', 'Student vacated from hostel.');
    }

    // ── Rooms for a hostel (AJAX / select) ────────────────────────

    public function hostelRooms(Hostel $hostel)
    {
        $rooms = HostelRoom::where('hostel_id', $hostel->id)
            ->where('status', 'available')
            ->get(['id', 'room_no', 'floor', 'type', 'capacity', 'occupied', 'monthly_fee', 'ac']);

        return response()->json($rooms);
    }
}
