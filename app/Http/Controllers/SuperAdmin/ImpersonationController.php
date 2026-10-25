<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Impersonation;
use App\Support\PlatformAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Start and stop "Log in as this user" */
class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $by = $request->user();
        $data = $request->validate([
            'reason' => 'required|string|min:10|max:500',
            'minutes' => ['required', 'integer', Rule::in(Impersonation::MINUTES)],
            'mode' => ['required', Rule::in(['look', 'act'])],
        ], [
            'reason.required' => 'Write why you need to sign in as this person.',
            'reason.min' => 'Write a little more (at least 10 letters), so others understand why.',
        ]);

        if ($why = Impersonation::refusal($by, $user)) {
            return back()->with('error', $why);
        }

        $readOnly = $data['mode'] === 'look' || ! Impersonation::mayAct($by);
        $minutes = (int) $data['minutes'];

        PlatformAudit::record('impersonate_start',
            "Signed in as {$user->name} ({$user->email}) for {$minutes} minutes".($readOnly ? ', look only' : ', changes allowed').' — reason: '.trim($data['reason']),
            ['user' => $by, 'school_id' => $user->school_id, 'details' => ['reason' => trim($data['reason']), 'target_user_id' => $user->id, 'minutes' => $minutes, 'read_only' => $readOnly]],
            $request);
        // The line above is the record; the general logger must not write a second one under the wrong name
        $request->attributes->set('audit_skip', true);

        Impersonation::start($request, $by, $user, $minutes, $readOnly, trim($data['reason']));

        return redirect()->route('dashboard')->with('info', "You are now seeing the app as {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        if (! Impersonation::active()) {
            return redirect()->route('dashboard');
        }

        $to = Impersonation::stop($request);
        $request->attributes->set('audit_skip', true);

        return redirect($to)->with('success', 'You are back in your own account.');
    }
}
