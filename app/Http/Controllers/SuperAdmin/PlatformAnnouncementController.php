<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PlatformAnnouncement;
use App\Models\School;
use App\Services\PlatformAnnouncementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/** Platform announcements and planned-maintenance notices to schools. Drafts can be edited; once published a notice is only ended or deleted, never rewritten. */
class PlatformAnnouncementController extends Controller
{
    /** Dates are typed in Nigerian time and kept in UTC */
    private const ZONE = 'Africa/Lagos';

    public function index(PlatformAnnouncementService $service): Response
    {
        $rows = PlatformAnnouncement::with('package:id,name')->latest()->paginate(20);

        return Inertia::render('SuperAdmin/Announcements/Index', [
            'announcements' => $rows->through(fn (PlatformAnnouncement $a) => [
                'id' => $a->id, 'title' => $a->title, 'body' => $a->body, 'kind' => $a->kind, 'audience' => $a->audience,
                'audience_label' => $this->audienceLabel($a), 'package_id' => $a->package_id, 'school_ids' => $a->school_ids ?? [],
                'show_banner' => $a->show_banner, 'send_bell' => $a->send_bell, 'send_email' => $a->send_email,
                'starts_at' => $a->starts_at?->toIso8601String(), 'ends_at' => $a->ends_at?->toIso8601String(),
                'status' => $a->status, 'live' => $a->status === 'published' && (! $a->ends_at || $a->ends_at->isFuture()),
                'published_at' => $a->published_at?->toIso8601String(),
                'bell_count' => $a->bell_count, 'email_count' => $a->email_count, 'email_failed' => $a->email_failed,
            ]),
            'packages' => Package::orderBy('name')->get(['id', 'name']),
            'schools'  => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, PlatformAnnouncementService $service): RedirectResponse
    {
        $a = PlatformAnnouncement::create($this->validated($request) + ['created_by' => $request->user()->id, 'status' => 'draft']);
        activity()->causedBy($request->user())->performedOn($a)->log('Announcement drafted');

        if ($request->boolean('publish')) {
            return $this->send($request, $service, $a);
        }

        return back()->with('success', 'Saved as a draft. Nothing has been sent.');
    }

    public function update(Request $request, PlatformAnnouncementService $service, PlatformAnnouncement $announcement): RedirectResponse
    {
        if ($announcement->status !== 'draft') {
            return back()->with('error', 'A published announcement cannot be changed. End it and write a new one.');
        }

        $announcement->update($this->validated($request));
        activity()->causedBy($request->user())->performedOn($announcement)->log('Announcement edited');

        return $request->boolean('publish') ? $this->send($request, $service, $announcement) : back()->with('success', 'Draft saved.');
    }

    public function publish(Request $request, PlatformAnnouncementService $service, PlatformAnnouncement $announcement): RedirectResponse
    {
        return $this->send($request, $service, $announcement);
    }

    /** Take a published notice down now: the banner goes away. Bell notices and emails already sent stay sent. */
    public function end(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        if ($announcement->status !== 'published') {
            return back()->with('error', 'Only a published announcement can be ended.');
        }

        $announcement->update(['status' => 'ended', 'ends_at' => now()]);
        activity()->causedBy($request->user())->performedOn($announcement)->log('Announcement ended');

        return back()->with('success', 'Ended. The banner is gone; notices already sent stay with the schools.');
    }

    public function destroy(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        if ($announcement->status === 'published') {
            return back()->with('error', 'End it first, then delete.');
        }

        $announcement->delete();
        activity()->causedBy($request->user())->log('Announcement deleted: '.$announcement->title);

        return back()->with('success', 'Deleted.');
    }

    private function send(Request $request, PlatformAnnouncementService $service, PlatformAnnouncement $a): RedirectResponse
    {
        if ($a->status !== 'draft') {
            return back()->with('error', 'This has already been published.');
        }
        if (! $a->show_banner && ! $a->send_bell && ! $a->send_email) {
            return back()->with('error', 'Choose at least one way to send it: banner, bell or email.');
        }
        if ($a->audience !== 'all' && count($a->schoolIds()) === 0) {
            return back()->with('error', 'No school matches this audience, so nobody would get it.');
        }

        $service->publish($a, $request->user());
        activity()->causedBy($request->user())->performedOn($a)
            ->withProperties(['audience' => $a->audience, 'bell' => $a->bell_count, 'email' => $a->email_count, 'email_failed' => $a->email_failed])->log('Announcement published');

        $parts = [];
        if ($a->show_banner) $parts[] = 'banner is showing';
        if ($a->send_bell) $parts[] = $a->bell_count.' bell notices sent';
        if ($a->send_email) $parts[] = $a->email_count.' emails sent'.($a->email_failed ? ' ('.$a->email_failed.' failed)' : '');

        return back()->with('success', 'Published: '.implode(', ', $parts).'.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'      => 'required|string|max:150',
            'body'       => 'required|string|max:2000',
            'kind'       => 'required|in:notice,maintenance',
            'audience'   => 'required|in:all,package,schools',
            'package_id' => 'required_if:audience,package|nullable|exists:packages,id',
            'school_ids' => 'required_if:audience,schools|nullable|array',
            'school_ids.*' => 'integer|exists:schools,id',
            'show_banner' => 'boolean', 'send_bell' => 'boolean', 'send_email' => 'boolean',
            'starts_at'  => 'nullable|date', 'ends_at' => 'nullable|date',
        ]);

        $starts = $data['starts_at'] ?? null ? Carbon::parse($data['starts_at'], self::ZONE)->utc() : null;
        $ends = $data['ends_at'] ?? null ? Carbon::parse($data['ends_at'], self::ZONE)->utc() : null;

        if ($data['kind'] === 'maintenance' && (! $starts || ! $ends)) {
            throw ValidationException::withMessages(['starts_at' => 'Say when the maintenance starts and ends.']);
        }
        if ($starts && $ends && $ends->lte($starts)) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }

        return [
            'title' => trim($data['title']), 'body' => trim($data['body']), 'kind' => $data['kind'], 'audience' => $data['audience'],
            'package_id' => $data['audience'] === 'package' ? $data['package_id'] : null,
            'school_ids' => $data['audience'] === 'schools' ? array_values(array_unique(array_map('intval', $data['school_ids'] ?? []))) : null,
            'show_banner' => (bool) ($data['show_banner'] ?? false), 'send_bell' => (bool) ($data['send_bell'] ?? false), 'send_email' => (bool) ($data['send_email'] ?? false),
            'starts_at' => $starts, 'ends_at' => $ends,
        ];
    }

    private function audienceLabel(PlatformAnnouncement $a): string
    {
        return match ($a->audience) {
            'package' => 'Plan: '.($a->package?->name ?? 'removed plan'),
            'schools' => count($a->school_ids ?? []).' chosen '.(count($a->school_ids ?? []) === 1 ? 'school' : 'schools'),
            default   => 'Every school',
        };
    }
}
