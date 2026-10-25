<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformApproval;
use App\Support\RiskyActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The second person's side of risky actions: see what a colleague wants to do and why, then approve or turn it
 * down. Nobody can answer their own request. An approval lets that one person do that one action, once, within a day.
 */
class PlatformApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $row = fn (PlatformApproval $a) => [
            'id' => $a->id, 'label' => $a->label, 'target_type' => $a->target_type, 'target' => $a->target_name,
            'reason' => $a->reason, 'by' => $a->requested_by_name, 'mine' => $a->requested_by === $request->user()->id,
            'status' => $a->isStale() ? 'expired' : $a->status, 'at' => $a->created_at?->toIso8601String(),
            'decided_by' => $a->decided_by_name, 'decided_at' => $a->decided_at?->toIso8601String(), 'note' => $a->decision_note,
            'expires_at' => $a->expires_at?->toIso8601String(),
        ];

        return Inertia::render('SuperAdmin/Approvals/Index', [
            'switched_on' => RiskyActions::secondApprovalOn(),
            'waiting' => PlatformApproval::where('status', 'pending')->where('created_at', '>', now()->subDays(PlatformApproval::PENDING_DAYS))
                ->oldest()->get()->map($row)->values(),
            'recent' => PlatformApproval::where('status', '!=', 'pending')->latest('id')->limit(30)->get()->map($row)->values(),
        ]);
    }

    public function approve(Request $request, PlatformApproval $approval): RedirectResponse
    {
        return $this->decide($request, $approval, true);
    }

    public function deny(Request $request, PlatformApproval $approval): RedirectResponse
    {
        return $this->decide($request, $approval, false);
    }

    private function decide(Request $request, PlatformApproval $approval, bool $yes): RedirectResponse
    {
        $request->attributes->set('audit_target', trim("{$approval->label}: {$approval->target_name} (asked by {$approval->requested_by_name})", ': '));
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        if ($approval->requested_by === $request->user()->id) {
            return back()->with('error', 'You cannot answer your own request. Ask someone else.');
        }
        if ($approval->status !== 'pending' || $approval->isStale()) {
            return back()->with('error', 'This request has already been answered or has run out of time.');
        }

        // Only one answer can win if two people press at once
        $won = PlatformApproval::whereKey($approval->id)->where('status', 'pending')->update([
            'status' => $yes ? 'approved' : 'denied',
            'decided_by' => $request->user()->id, 'decided_by_name' => $request->user()->name, 'decided_at' => now(),
            'decision_note' => $data['note'] ?? null,
            'expires_at' => $yes ? now()->addHours(PlatformApproval::APPROVED_HOURS) : null,
        ]);

        if (! $won) {
            return back()->with('error', 'Someone else just answered this request.');
        }

        return back()->with('success', $yes
            ? "Approved. {$approval->requested_by_name} can now do it once in the next ".PlatformApproval::APPROVED_HOURS.' hours.'
            : 'Turned down.');
    }
}
