<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\SchoolLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Risky changes to a whole school. Each one needs a written reason and is logged. */
class SchoolLifecycleController extends Controller
{
    public function __construct(private SchoolLifecycleService $lifecycle) {}

    public function scheduleDeletion(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:500', 'confirm_name' => 'required|string']);

        if (mb_strtolower(trim($data['confirm_name'])) !== mb_strtolower($school->name)) {
            return back()->withErrors(['confirm_name' => 'Type the school name exactly to confirm.']);
        }
        if ($school->delete_scheduled_at) {
            return back()->with('error', 'Deletion is already scheduled for this school.');
        }

        $this->lifecycle->scheduleDeletion($school, $request->user(), trim($data['reason']));

        return back()->with('success', "Deletion scheduled for {$school->delete_scheduled_at->format('j M Y')}. The school is paused now and nothing is removed until then.");
    }

    public function cancelDeletion(Request $request, School $school): RedirectResponse
    {
        if (! $school->delete_scheduled_at) {
            return back()->with('error', 'No deletion is scheduled.');
        }

        $this->lifecycle->cancelDeletion($school, $request->user());

        return back()->with('success', 'Deletion cancelled. The school is back to how it was.');
    }

    public function restore(Request $request, int $school): RedirectResponse
    {
        $row = School::onlyTrashed()->findOrFail($school);
        $this->lifecycle->restore($row, $request->user());

        return back()->with('success', "{$row->name} restored. It is suspended until you activate it.");
    }
}
