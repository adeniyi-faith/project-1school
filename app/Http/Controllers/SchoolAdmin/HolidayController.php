<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SchoolAdmin/Holidays/Index', [
            'holidays' => Holiday::orderBy('date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:300',
        ]);

        Holiday::create($data);

        return back()->with('success', 'Holiday added.');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'date'        => 'required|date',
            'description' => 'nullable|string|max:300',
        ]);

        $holiday->update($data);

        return back()->with('success', 'Holiday updated.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Holiday deleted.');
    }
}
