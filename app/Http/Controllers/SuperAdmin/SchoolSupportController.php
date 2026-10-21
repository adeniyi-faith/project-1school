<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolNote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The few things the platform team can change from the School 360 page: tags, account manager and private notes. Each change is logged. */
class SchoolSupportController extends Controller
{
    public function profile(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'tags'               => 'nullable|array|max:10',
            'tags.*'             => 'string|max:30',
            'account_manager_id' => 'nullable|integer',
        ]);

        $managerId = $data['account_manager_id'] ?? null;
        if ($managerId && ! User::role('super-admin')->whereKey($managerId)->exists()) {
            return back()->withErrors(['account_manager_id' => 'Pick someone from the platform team.']);
        }

        $tags = collect($data['tags'] ?? [])->map(fn ($t) => mb_strtolower(trim($t)))->filter()->unique()->values()->all();

        $school->forceFill(['tags' => $tags ?: null, 'account_manager_id' => $managerId])->save();

        activity()->causedBy($request->user())->performedOn($school)
            ->withProperties(['tags' => $tags, 'account_manager_id' => $managerId])->log('School tags or account manager changed');

        return back()->with('success', 'Saved.');
    }

    public function storeNote(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:2000', 'pinned' => 'boolean']);

        $note = SchoolNote::create([
            'school_id' => $school->id,
            'user_id'   => $request->user()->id,
            'body'      => trim($data['body']),
            'pinned'    => (bool) ($data['pinned'] ?? false),
        ]);

        activity()->causedBy($request->user())->performedOn($school)->withProperties(['note_id' => $note->id])->log('Internal note added');

        return back()->with('success', 'Note added.');
    }

    public function togglePin(Request $request, School $school, int $note): RedirectResponse
    {
        $row = SchoolNote::withoutGlobalScopes()->where('school_id', $school->id)->findOrFail($note);
        $row->update(['pinned' => ! $row->pinned]);

        return back();
    }

    public function destroyNote(Request $request, School $school, int $note): RedirectResponse
    {
        $row = SchoolNote::withoutGlobalScopes()->where('school_id', $school->id)->findOrFail($note);
        $row->delete();

        activity()->causedBy($request->user())->performedOn($school)->withProperties(['note_id' => $note])->log('Internal note deleted');

        return back()->with('success', 'Note deleted.');
    }
}
