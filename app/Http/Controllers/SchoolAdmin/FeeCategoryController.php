<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeCategoryController extends Controller
{
    public function index()
    {
        $sid = $this->getSchoolId();

        $categories = FeeCategory::where('school_id', $sid)
            ->withCount('feeStructures')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolAdmin/Fees/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:tuition,transport,library,exam,hostel,sports,other',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        FeeCategory::create(array_merge($data, [
            'school_id' => $this->getSchoolId(),
            'is_active' => $data['is_active'] ?? true,
        ]));

        return back()->with('success', 'Fee category created.');
    }

    public function update(Request $request, FeeCategory $feeCategory)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:tuition,transport,library,exam,hostel,sports,other',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $feeCategory->update($data);

        return back()->with('success', 'Fee category updated.');
    }

    public function destroy(FeeCategory $feeCategory)
    {
        $feeCategory->delete();
        return back()->with('success', 'Fee category deleted.');
    }
}
