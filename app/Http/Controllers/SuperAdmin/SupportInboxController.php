<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolNotification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The platform team's side of the support inbox: see every school's requests, assign, reply (or leave a private note) and close. Every action is logged. */
class SupportInboxController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'active');
        $mine = $request->input('assigned', 'all');

        $q = SupportTicket::withoutGlobalScopes()->with(['school:id,name', 'requester:id,name', 'assignee:id,name'])
            ->when($status === 'active', fn ($q) => $q->whereIn('status', ['open', 'answered']))
            ->when(in_array($status, SupportTicket::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($mine === 'me', fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when($mine === 'none', fn ($q) => $q->whereNull('assigned_to'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->where(fn ($w) => $w->where('subject', 'like', $term)->orWhereHas('school', fn ($s) => $s->where('name', 'like', $term)));
            })
            // Urgent first, then the ones waiting longest for us
            ->orderByRaw("case when priority = 'urgent' and status = 'open' then 0 else 1 end")
            ->orderByRaw("case status when 'open' then 0 when 'answered' then 1 else 2 end")
            ->orderBy('last_reply_at');

        $count = fn (string $s) => SupportTicket::withoutGlobalScopes()->where('status', $s)->count();

        return Inertia::render('SuperAdmin/Support/Index', [
            'tickets' => $q->paginate(20)->withQueryString()->through(fn (SupportTicket $t) => $this->row($t)),
            'filters' => ['status' => $status, 'assigned' => $mine, 'search' => $request->input('search', '')],
            'counts'  => ['open' => $count('open'), 'answered' => $count('answered'), 'closed' => $count('closed')],
        ]);
    }

    public function show(int $ticket): Response
    {
        $t = $this->find($ticket)->load(['school:id,name', 'requester:id,name,email', 'assignee:id,name']);

        return Inertia::render('SuperAdmin/Support/Show', [
            'ticket'   => $this->row($t) + ['requester_email' => $t->requester?->email, 'school_id' => $t->school_id],
            'messages' => SupportMessage::withoutGlobalScopes()->where('support_ticket_id', $t->id)->with('author:id,name')->oldest()->get()
                ->map(fn (SupportMessage $m) => [
                    'id' => $m->id, 'body' => $m->body, 'from_staff' => $m->from_staff, 'internal' => $m->internal,
                    'author' => $m->author?->name ?? 'Former user', 'at' => $m->created_at?->toIso8601String(),
                ]),
            'team'     => User::role('super-admin')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function reply(Request $request, int $ticket): RedirectResponse
    {
        $t = $this->find($ticket);
        $data = $request->validate(['body' => 'required|string|max:5000', 'internal' => 'boolean']);
        $internal = (bool) ($data['internal'] ?? false);

        SupportMessage::withoutGlobalScopes()->create([
            'school_id' => $t->school_id, 'support_ticket_id' => $t->id, 'user_id' => $request->user()->id,
            'from_staff' => true, 'internal' => $internal, 'body' => trim($data['body']),
        ]);

        if (! $internal) {
            $t->update(['status' => 'answered', 'last_reply_at' => now(), 'closed_at' => null, 'assigned_to' => $t->assigned_to ?? $request->user()->id]);

            if ($t->user_id) {
                SchoolNotification::withoutGlobalScopes()->create([
                    'school_id' => $t->school_id, 'user_id' => $t->user_id, 'type' => 'support',
                    'title' => 'The platform team replied', 'body' => 'Your request "'.$t->subject.'" has a new reply. Open Help and support to read it.',
                    'data' => ['url' => '/school/support/'.$t->id], 'channel' => 'in-app',
                ]);
            }
        }

        activity()->causedBy($request->user())->performedOn($t)->withProperties(['school_id' => $t->school_id, 'internal' => $internal])
            ->log($internal ? 'Support private note added' : 'Support reply sent');

        return back()->with('success', $internal ? 'Note saved. The school cannot see it.' : 'Reply sent.');
    }

    public function assign(Request $request, int $ticket): RedirectResponse
    {
        $t = $this->find($ticket);
        $data = $request->validate(['assigned_to' => 'nullable|integer']);
        $to = $data['assigned_to'] ?? null;

        if ($to && ! User::role('super-admin')->whereKey($to)->exists()) {
            return back()->withErrors(['assigned_to' => 'Pick someone from the platform team.']);
        }

        $t->update(['assigned_to' => $to]);
        activity()->causedBy($request->user())->performedOn($t)->withProperties(['school_id' => $t->school_id, 'assigned_to' => $to])->log('Support request assigned');

        return back()->with('success', $to ? 'Assigned.' : 'Now unassigned.');
    }

    public function status(Request $request, int $ticket): RedirectResponse
    {
        $t = $this->find($ticket);
        $data = $request->validate(['status' => 'required|in:open,closed']);

        $t->update(['status' => $data['status'], 'closed_at' => $data['status'] === 'closed' ? now() : null]);
        activity()->causedBy($request->user())->performedOn($t)->withProperties(['school_id' => $t->school_id, 'status' => $data['status']])
            ->log($data['status'] === 'closed' ? 'Support request closed' : 'Support request reopened');

        return back()->with('success', $data['status'] === 'closed' ? 'Closed.' : 'Reopened.');
    }

    private function find(int $id): SupportTicket
    {
        return SupportTicket::withoutGlobalScopes()->findOrFail($id);
    }

    private function row(SupportTicket $t): array
    {
        return [
            'id' => $t->id, 'subject' => $t->subject, 'school' => $t->school?->name, 'school_id' => $t->school_id,
            'category' => SupportTicket::CATEGORIES[$t->category] ?? $t->category, 'priority' => $t->priority, 'status' => $t->status,
            'by' => $t->requester?->name, 'assigned_to' => $t->assigned_to, 'assignee' => $t->assignee?->name,
            'last_reply_at' => $t->last_reply_at?->toIso8601String(), 'created_at' => $t->created_at?->toIso8601String(),
        ];
    }
}
