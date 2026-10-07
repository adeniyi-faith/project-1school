<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        $subjects = Subject::with('schoolClass')
            ->orderBy('name')
            ->get();

        $classes = SchoolClass::orderBy('numeric_name')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('SchoolAdmin/Subjects/Index', [
            'subjects' => $subjects,
            'classes'  => $classes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'name'       => 'required|string|max:150',
            'code'       => 'nullable|string|max:20',
            'type'       => 'required|in:theory,practical',
            'full_marks' => 'nullable|integer|min:1',
            'pass_marks' => 'nullable|integer|min:1',
        ]);

        Subject::create($data);

        return back()->with('success', 'Subject created.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $data = $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'name'       => 'required|string|max:150',
            'code'       => 'nullable|string|max:20',
            'type'       => 'required|in:theory,practical',
            'full_marks' => 'nullable|integer|min:1',
            'pass_marks' => 'nullable|integer|min:1',
        ]);

        $subject->update($data);

        return back()->with('success', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', 'Subject deleted.');
    }
}
