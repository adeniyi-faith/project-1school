<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/** Onboarding tracker: how far each school has got with setup, which ones look stuck, and a nudge button. Looking is free; a nudge sends a notice and an email and is logged. */
class OnboardingController extends Controller
{
    public function index(Request $request, OnboardingService $service): Response
    {
        $rows = $service->rows();
        $filter = in_array($request->input('show'), ['stuck', 'done', 'all'], true) ? $request->input('show') : 'stuck';

        $shown = $rows->when($filter === 'stuck', fn ($c) => $c->where('stuck', true))
            ->when($filter === 'done', fn ($c) => $c->filter(fn ($r) => $r['done'] === $r['total']))
            // Most stuck first (more reasons), then the least set up
            ->sortBy([fn ($a, $b) => count($b['reasons']) <=> count($a['reasons']), fn ($a, $b) => $a['done'] <=> $b['done']])->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paged = new LengthAwarePaginator($shown->forPage($page, 20)->values(), $shown->count(), 20, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return Inertia::render('SuperAdmin/Onboarding/Index', [
            'schools' => $paged,
            'show'    => $filter,
            'counts'  => [
                'all' => $rows->count(), 'stuck' => $rows->where('stuck', true)->count(),
                'done' => $rows->filter(fn ($r) => $r['done'] === $r['total'])->count(),
                'never' => $rows->filter(fn ($r) => in_array('never_signed_in', $r['reasons'], true))->count(),
            ],
            'reasons' => OnboardingService::REASONS,
        ]);
    }

    /** What the nudge would say, so the page can show it for editing */
    public function draft(OnboardingService $service, School $school): \Illuminate\Http\JsonResponse
    {
        $row = $service->rows()->firstWhere('id', $school->id);

        return response()->json(['message' => $service->defaultMessage($school, $row['missing'] ?? [])]);
    }

    public function nudge(Request $request, OnboardingService $service, School $school): RedirectResponse
    {
        $data = $request->validate(['message' => 'required|string|min:10|max:1000']);
        $row = $service->rows()->firstWhere('id', $school->id);

        if (! $row) {
            return back()->with('error', 'Only active schools can be nudged.');
        }
        if (! $row['can_nudge']) {
            return back()->with('error', 'This school was nudged less than '.OnboardingService::NUDGE_GAP_DAYS.' days ago. Give them a little time.');
        }

        $nudge = $service->nudge($school, $request->user(), trim($data['message']));
        activity()->causedBy($request->user())->performedOn($school)
            ->withProperties(['bell' => $nudge->bell_count, 'email' => $nudge->email_count, 'email_failed' => $nudge->email_failed])->log('Onboarding nudge sent');

        return back()->with('success', "Sent to {$school->name}: {$nudge->bell_count} bell notice(s), {$nudge->email_count} email(s)".($nudge->email_failed ? " ({$nudge->email_failed} failed)" : '').'.');
    }
}
