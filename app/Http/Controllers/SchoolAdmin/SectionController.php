<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(): Response
    {
        $classes = SchoolClass::with(['sections' => fn ($q) => $q->orderBy('name')])
            ->orderBy('numeric_name')
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolAdmin/Sections/Index', [
            'classes' => $classes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:0',
        ]);

        Section::create($data);

        return back()->with('success', 'Section created.');
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:0',
        ]);

        $section->update($data);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }
}
