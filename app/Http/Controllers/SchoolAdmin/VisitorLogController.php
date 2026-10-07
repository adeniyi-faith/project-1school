<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorLogController extends Controller
{
    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $visitors = VisitorLog::where('school_id', $sid)
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name',           'like', "%{$request->search}%")
                   ->orWhere('phone',         'like', "%{$request->search}%")
                   ->orWhere('person_to_meet','like', "%{$request->search}%");
            }))
            ->when($request->date, fn ($q) => $q->whereDate('time_in', $request->date))
            ->latest('time_in')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Admissions/Visitors', [
            'visitors' => $visitors,
            'filters'  => $request->only('search', 'date'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:150',
            'phone'          => 'required|string|max:25',
            'purpose'        => 'required|string|max:150',
            'person_to_meet' => 'required|string|max:150',
            'remarks'        => 'nullable|string',
        ]);

        VisitorLog::create(array_merge($data, [
            'school_id' => $this->getSchoolId(),
            'time_in'   => now(),
        ]));

        return back()->with('success', 'Visitor checked in.');
    }

    public function checkout(VisitorLog $visitorLog)
    {
        if ($visitorLog->school_id !== $this->getSchoolId()) abort(403);

        if ($visitorLog->time_out) {
            return back()->with('error', 'Already checked out.');
        }

        $visitorLog->update(['time_out' => now()]);

        return back()->with('success', 'Visitor checked out.');
    }

    public function destroy(VisitorLog $visitorLog)
    {
        if ($visitorLog->school_id !== $this->getSchoolId()) abort(403);
        $visitorLog->delete();

        return back()->with('success', 'Record deleted.');
    }
}
