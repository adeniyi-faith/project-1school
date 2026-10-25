<?php

namespace App\Http\Middleware;

use App\Models\PlatformApproval;
use App\Support\PlatformAccess;
use App\Support\RiskyActions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops risky platform actions (see RiskyActions) until the person has written a reason, typed the name where
 * that is asked, and, when second approval is on, someone else has approved. What was said is passed on to the
 * activity log through request attributes.
 */
class ConfirmRiskyAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $rule = RiskyActions::rule($request->route()?->getName());

        if (! $rule || $request->isMethodSafe() || ! RiskyActions::enabled() || ! PlatformAccess::isPlatform($user)) {
            return $next($request);
        }

        $target = RiskyActions::target($rule, $request);
        $reason = trim((string) $request->input('reason'));
        $request->attributes->set('audit_target', $target['name']);

        if (mb_strlen($reason) < RiskyActions::MIN_REASON) {
            return $this->stop($request, 'reason', 'Write why you are doing this (at least '.RiskyActions::MIN_REASON.' letters). It is kept in the staff activity log.');
        }

        if ($rule['type_name'] && ! RiskyActions::sameName((string) $request->input('confirm_name'), $target['name'])) {
            return $this->stop($request, 'confirm_name', 'Type the name "'.$target['name'].'" exactly to confirm.');
        }

        if (RiskyActions::needsApproval($rule, $user)) {
            $approval = RiskyActions::consume($user, $request->route()->getName(), $target['id']);

            if (! $approval) {
                $waiting = PlatformApproval::where('requested_by', $user->id)->where('action', $request->route()->getName())
                    ->where('target_id', $target['id'])->where('status', 'pending')
                    ->where('created_at', '>', now()->subDays(PlatformApproval::PENDING_DAYS))->exists();

                if (! $waiting) {
                    PlatformApproval::create([
                        'action' => $request->route()->getName(), 'label' => $rule['label'], 'target_type' => $rule['type'],
                        'target_id' => $target['id'], 'target_name' => $target['name'], 'reason' => $reason,
                        'requested_by' => $user->id, 'requested_by_name' => $user->name,
                    ]);
                }
                $request->attributes->set('audit_action', 'approval_requested');
                $request->attributes->set('audit_reason', $reason);

                return back()->with('success', $waiting
                    ? 'This is already waiting for approval. Ask someone who can approve, then try again once it is approved.'
                    : 'Sent for approval. Once someone else approves it, come back and do it again.');
            }

            $request->attributes->set('audit_approved_by', $approval->decided_by_name);
        }

        $request->attributes->set('audit_reason', $reason);

        return $next($request);
    }

    /** Turn the request back with a plain message on the field that is wrong */
    private function stop(Request $request, string $field, string $message): Response
    {
        $request->attributes->set('audit_refused', true);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
        }

        return back()->withErrors([$field => $message])->with('error', $message);
    }
}
