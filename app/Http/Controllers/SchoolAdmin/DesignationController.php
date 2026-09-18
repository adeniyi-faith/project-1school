<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function index(): Response
    {
        $designations = Designation::with('department:id,name')
            ->withCount('staff')
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolAdmin/Designations/Index', [
            'designations' => $designations,
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
        ]);

        Designation::create($data);

        return back()->with('success', 'Designation created.');
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
        ]);

        $designation->update($data);

        return back()->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
