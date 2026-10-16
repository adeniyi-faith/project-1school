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
        $living = HostelAllocation::where('hostel_id', $hostel->id)->where('status', 'active')->count();
        if ($living > 0) {
            return back()->with('error', "{$hostel->name} still has {$living} " . ($living === 1 ? 'student' : 'students') . ' living in it. Vacate them first, then delete it.');
        }
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

        abort_unless($room->hostel_id === $hostel->id, 404);
        $taken = HostelAllocation::where('room_id', $room->id)->where('status', 'active')->count();
        if ((int) $data['capacity'] < $taken) {
            return back()->withErrors(['capacity' => "{$taken} " . ($taken === 1 ? 'bed is' : 'beds are') . " in use in this room, so its capacity cannot be less than {$taken}."]);
        }

        $oldCapacity = $room->capacity;
        // A room under repair stays closed; otherwise it is full exactly when every bed is taken
        $data['status'] = $data['status'] === 'maintenance' ? 'maintenance' : ($taken >= (int) $data['capacity'] ? 'full' : 'available');
        $data['occupied'] = $taken;
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
        abort_unless($room->hostel_id === $hostel->id, 404);
        $taken = HostelAllocation::where('room_id', $room->id)->where('status', 'active')->count();
        if ($taken > 0) {
            return back()->with('error', "Room {$room->room_no} still has {$taken} " . ($taken === 1 ? 'student' : 'students') . ' in it. Vacate or move them first, then delete the room.');
        }
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
        $hostel = Hostel::findOrFail($data['hostel_id']);
        $student = Student::findOrFail($data['student_id']);

        if ($problem = $this->roomProblem($room, $hostel, $student)) {
            return back()->withErrors($problem);
        }

        // Check student not already active in another room
        $existing = HostelAllocation::where('student_id', $data['student_id'])
            ->where('status', 'active')->first();
        if ($existing) {
            return back()->withErrors(['student_id' => 'Student is already allocated to a hostel room.']);
        }

        $data['school_id'] = $this->getSchoolId();
        $data['status']    = 'active';

        $full = DB::transaction(function () use ($data, $room) {
            // Look at the room again while holding it, so two people booking the last bed cannot both get it
            $room = HostelRoom::whereKey($room->id)->lockForUpdate()->first();
            if ($room->occupied >= $room->capacity) {
                return true;
            }
            HostelAllocation::create($data);
            $room->increment('occupied');
            if ($room->fresh()->occupied >= $room->capacity) {
                $room->update(['status' => 'full']);
            }

            return false;
        });
        if ($full) {
            return back()->withErrors(['room_id' => 'Room is at full capacity.']);
        }

        return back()->with('success', 'Student allocated to hostel room.');
    }

    /** Change a saved allocation: move to another room or bed, fix the joining date or the notes */
    public function updateAllocation(Request $request, HostelAllocation $allocation)
    {
        if ($allocation->status !== 'active') {
            return back()->with('error', 'This student has already left, so the allocation cannot be changed.');
        }

        $data = $request->validate([
            'hostel_id'    => 'required|exists:hostels,id',
            'room_id'      => 'required|exists:hostel_rooms,id',
            'bed_no'       => 'nullable|string|max:20',
            'joining_date' => 'required|date',
            'fee_linked'   => 'boolean',
            'notes'        => 'nullable|string',
        ]);

        $newRoom = HostelRoom::findOrFail($data['room_id']);
        $moving = $newRoom->id !== $allocation->room_id;
        $hostel = Hostel::findOrFail($data['hostel_id']);
        // Staying in the same room only needs the hostel/gender rules; moving needs an open room with a free bed too
        if ($problem = $this->roomProblem($newRoom, $hostel, $allocation->student, ! $moving)) {
            return back()->withErrors($problem);
        }

        DB::transaction(function () use ($allocation, $data, $newRoom, $moving) {
            if ($moving) {
                $old = $allocation->room;
                $old->decrement('occupied');
                if ($old->status === 'full') {
                    $old->update(['status' => 'available']);
                }
                $newRoom->increment('occupied');
                if ($newRoom->fresh()->occupied >= $newRoom->capacity) {
                    $newRoom->update(['status' => 'full']);
                }
            }
            $allocation->update($data);
        });

        return back()->with('success', $moving ? 'Student moved to the new room.' : 'Allocation updated.');
    }

    /** Find students by name or admission number for the allocation form */
    public function studentSearch(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $taken = HostelAllocation::where('status', 'active')->pluck('student_id');

        return response()->json(['data' => Student::where('status', 'active')->whereNotIn('id', $taken)
            ->where(fn ($w) => $w->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('admission_no', 'like', "%{$q}%"))
            ->orderBy('first_name')->limit(20)->get(['id', 'first_name', 'last_name', 'admission_no'])]);
    }

    public function vacate(Request $request, HostelAllocation $allocation)
    {
        $data = $request->validate([
            'leaving_date' => 'required|date',
        ]);

        if ($allocation->status !== 'active') {
            return back()->with('error', 'This student has already left, so there is nobody to vacate.');
        }

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

    public function hostelRooms(Request $request, Hostel $hostel)
    {
        $rooms = HostelRoom::where('hostel_id', $hostel->id)
            ->where(fn ($q) => $q->where('status', 'available')->orWhere('id', $request->integer('include')))
            ->get(['id', 'room_no', 'floor', 'type', 'capacity', 'occupied', 'monthly_fee', 'ac']);

        return response()->json($rooms);
    }

    /**
     * Why this student cannot have a bed in this room, as a form error, or null when they can:
     * the room is in the chosen hostel, is open, has a free bed (unless the student already sleeps there),
     * and a boys' hostel only takes boys and a girls' hostel only girls.
     */
    private function roomProblem(HostelRoom $room, Hostel $hostel, Student $student, bool $alreadyThere = false): ?array
    {
        if ($room->hostel_id !== $hostel->id) {
            return ['room_id' => 'That room is not in the chosen hostel.'];
        }
        if ($room->status === 'maintenance') {
            return ['room_id' => "Room {$room->room_no} is closed for repairs."];
        }
        if (! $alreadyThere && $room->occupied >= $room->capacity) {
            return ['room_id' => 'Room is at full capacity.'];
        }
        $needs = ['boys' => 'male', 'girls' => 'female'][$hostel->type] ?? null;
        if ($needs && $student->gender !== $needs) {
            return ['student_id' => "{$student->first_name} cannot be placed in {$hostel->name}: it is a " . ($hostel->type === 'boys' ? "boys'" : "girls'") . ' hostel.'];
        }

        return null;
    }
}
