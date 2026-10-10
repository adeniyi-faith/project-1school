<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SchoolRequest;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        $schools = School::withTrashed(false)
            ->withCount('users')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Schools/Index', [
            'schools' => [
                'data'  => $schools->items(),
                'meta'  => [
                    'total'        => $schools->total(),
                    'per_page'     => $schools->perPage(),
                    'current_page' => $schools->currentPage(),
                    'last_page'    => $schools->lastPage(),
                    'from'         => $schools->firstItem(),
                    'to'           => $schools->lastItem(),
                ],
                'links' => [
                    'first' => $schools->url(1),
                    'last'  => $schools->url($schools->lastPage()),
                    'prev'  => $schools->previousPageUrl(),
                    'next'  => $schools->nextPageUrl(),
                ],
            ],
            'filters' => $request->only('search', 'status'),
            'stats'   => [
                'total'     => School::count(),
                'active'    => School::where('status', 'active')->count(),
                'suspended' => School::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SuperAdmin/Schools/Create');
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $school = School::create($data);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School created');

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', "School \"{$school->name}\" created successfully.");
    }

    public function show(School $school): Response
    {
        $school->load(['academicYears' => fn ($q) => $q->latest()]);
        $school->loadCount('users');

        return Inertia::render('SuperAdmin/Schools/Show', [
            'school' => $school,
        ]);
    }

    public function edit(School $school): Response
    {
        return Inertia::render('SuperAdmin/Schools/Edit', [
            'school' => $school,
        ]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School updated');

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', "School \"{$school->name}\" updated.");
    }

    public function suspend(Request $request, School $school): RedirectResponse
    {
        $school->update(['status' => 'suspended']);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School suspended');

        return back()->with('success', "School \"{$school->name}\" suspended.");
    }

    public function activate(Request $request, School $school): RedirectResponse
    {
        $school->update(['status' => 'active']);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School activated');

        return back()->with('success', "School \"{$school->name}\" activated.");
    }

    public function destroy(School $school): RedirectResponse
    {
        $school->delete();

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', "School deleted.");
    }
}
