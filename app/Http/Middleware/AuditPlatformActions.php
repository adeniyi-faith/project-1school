<?php

namespace App\Http\Middleware;

use App\Support\ActingSchool;
use App\Support\PlatformAccess;
use App\Support\PlatformAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes down what the platform team does: every change they make (also the ones that were turned back), every
 * school entered or left, every page staff open inside a school, and every platform page they were refused.
 * It never changes the response, and a problem while writing the line never breaks the page.
 */
class AuditPlatformActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! PlatformAudit::enabled() || ! PlatformAccess::isPlatform($user)) {
            return $next($request);
        }

        $schoolBefore = ActingSchool::id();
        $response = $next($request);

        try {
            $this->write($request, $response, $schoolBefore);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function write(Request $request, Response $response, ?int $schoolBefore): void
    {
        $user = $request->user();
        $route = $request->route()?->getName();
        $status = $response->getStatusCode();
        $isStaff = PlatformAccess::isStaff($user);
        $inSchool = $schoolBefore !== null;
        $platformPage = $request->is('super-admin', 'super-admin/*');

        if ($route === 'logout' || $request->attributes->get('audit_skip')) {
            return;
        }

        $changes = PlatformAudit::takeChanges();
        $details = array_filter([
            'fields' => PlatformAudit::fieldNames($request),
            'changes' => $changes,
            'reason' => $request->attributes->get('audit_reason'),
        ]);

        if (! $request->isMethodSafe()) {
            $action = match (true) {
                $request->attributes->has('audit_action') => (string) $request->attributes->get('audit_action'),
                $route === 'super-admin.schools.enter' => 'enter_school',
                $route === 'super-admin.schools.leave' => 'leave_school',
                $status === 403 || $request->attributes->get('audit_refused') => 'refused',
                default => 'changed',
            };
            // Entering a school: the school is the one in the address (the session only changes on success)
            $schoolId = $action === 'enter_school' ? (int) ($request->route('school')?->id ?? 0) ?: null : ($schoolBefore ?? ActingSchool::id());
            PlatformAudit::record($action, PlatformAudit::describe($request), ['status' => $status, 'details' => $details ?: null, 'school_id' => $schoolId], $request);

            return;
        }

        // A platform page the person was not allowed to open
        if ($status === 403 && $platformPage) {
            PlatformAudit::record('refused', 'Tried to open '.($route ?? $request->path()).' but was not allowed', ['status' => $status], $request);

            return;
        }

        // Staff looking around inside a school: one line per page (not the small background feeds)
        if ($isStaff && $inSchool && $request->is('school', 'school/*') && ! $request->expectsJson() && ! str_ends_with($request->path(), '/feed')) {
            PlatformAudit::record('viewed', 'Opened /'.$request->path(), ['status' => $status, 'school_id' => $schoolBefore], $request);
        }
    }
}
