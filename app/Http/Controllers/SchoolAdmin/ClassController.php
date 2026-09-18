<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(): Response
    {
        $classes = SchoolClass::withCount(['sections', 'subjects'])
            ->orderBy('numeric_name')
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolAdmin/Classes/Index', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'numeric_name' => 'nullable|integer|min:1',
            'capacity'     => 'nullable|integer|min:0',
        ]);

        SchoolClass::create($data);

        return back()->with('success', 'Class created.');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'numeric_name' => 'nullable|integer|min:1',
            'capacity'     => 'nullable|integer|min:0',
        ]);

        $class->update($data);

        return back()->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return back()->with('success', 'Class deleted.');
    }
}
