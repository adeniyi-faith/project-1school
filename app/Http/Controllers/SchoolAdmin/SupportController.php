<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** A school asks the platform team for help and follows the answers. A school only ever sees its own requests, and never the staff's private notes. */
class SupportController extends Controller
{
    public function index(): Response
    {
        $tickets = SupportTicket::where('school_id', $this->getSchoolId())->with('requester:id,name')
            ->orderByRaw("case status when 'closed' then 1 else 0 end")->latest('last_reply_at')->paginate(20);

        return Inertia::render('SchoolAdmin/Support/Index', [
            'tickets'    => $tickets->through(fn (SupportTicket $t) => $this->row($t)),
            'categories' => SupportTicket::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:150',
            'category' => 'required|in:'.implode(',', array_keys(SupportTicket::CATEGORIES)),
            'priority' => 'required|in:normal,urgent',
            'body'     => 'required|string|max:5000',
        ]);

        $ticket = SupportTicket::create([
            'school_id' => $this->getSchoolId(), 'user_id' => $request->user()->id,
            'subject' => trim($data['subject']), 'category' => $data['category'], 'priority' => $data['priority'],
            'status' => 'open', 'last_reply_at' => now(),
        ]);
        SupportMessage::create(['school_id' => $ticket->school_id, 'support_ticket_id' => $ticket->id, 'user_id' => $request->user()->id, 'body' => trim($data['body'])]);

        return redirect()->route('school.support.show', $ticket)->with('success', 'Sent. The platform team will reply here.');
    }

    public function show(int $ticket): Response
    {
        $t = $this->find($ticket)->load('requester:id,name');

        return Inertia::render('SchoolAdmin/Support/Show', [
            'ticket'   => $this->row($t),
            'messages' => SupportMessage::where('school_id', $t->school_id)->where('support_ticket_id', $t->id)->where('internal', false)
                ->with('author:id,name')->oldest()->get()->map(fn (SupportMessage $m) => [
                    'id' => $m->id, 'body' => $m->body, 'from_staff' => $m->from_staff,
                    // The school sees "Platform team", not which staff member answered
                    'author' => $m->from_staff ? 'Platform team' : ($m->author?->name ?? 'Someone at your school'),
                    'at' => $m->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function reply(Request $request, int $ticket): RedirectResponse
    {
        $t = $this->find($ticket);
        $data = $request->validate(['body' => 'required|string|max:5000']);

        SupportMessage::create(['school_id' => $t->school_id, 'support_ticket_id' => $t->id, 'user_id' => $request->user()->id, 'body' => trim($data['body'])]);
        // A reply from the school puts it back in the team's queue, even if it had been closed
        $t->update(['status' => 'open', 'last_reply_at' => now(), 'closed_at' => null]);

        return back()->with('success', 'Reply sent.');
    }

    public function close(int $ticket): RedirectResponse
    {
        $this->find($ticket)->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', 'Request closed. Reply any time to open it again.');
    }

    private function find(int $id): SupportTicket
    {
        return SupportTicket::where('school_id', $this->getSchoolId())->findOrFail($id);
    }

    private function row(SupportTicket $t): array
    {
        return [
            'id' => $t->id, 'subject' => $t->subject, 'category' => SupportTicket::CATEGORIES[$t->category] ?? $t->category,
            'priority' => $t->priority, 'status' => $t->status, 'by' => $t->requester?->name,
            'last_reply_at' => $t->last_reply_at?->toIso8601String(), 'created_at' => $t->created_at?->toIso8601String(),
        ];
    }
}
