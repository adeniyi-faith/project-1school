<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EmailTemplate;
use App\Models\Message;
use App\Models\SchoolClass;
use App\Models\SchoolNotification;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommunicationController extends Controller
{
    // ── Announcements ─────────────────────────────────────────────

    public function announcements(Request $request)
    {
        $sid = $this->getSchoolId();

        $announcements = Announcement::with('author:id,name', 'schoolClass:id,name')
            ->where('school_id', $sid)
            ->when($request->audience, fn ($q) => $q->where('audience', $request->audience))
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Communication/Announcements', [
            'announcements' => $announcements,
            'classes'       => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'filters'       => $request->only('audience'),
            'can'           => ['create' => $request->user()->can('announcements.create'), 'delete' => $request->user()->can('announcements.delete')],
            'canEmailSubscribers' => $this->subscribersReachable($request),
        ]);
    }

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'audience'    => 'required|in:all,class,role',
            'class_id'    => 'nullable|exists:classes,id',
            'target_role' => 'nullable|string|max:50',
            'is_pinned'   => 'boolean',
            'published_at'=> 'nullable|date',
            'email_subscribers' => 'sometimes|boolean',
        ]);
        $alsoEmail = (bool) ($data['email_subscribers'] ?? false);
        unset($data['email_subscribers']);

        $data['school_id']  = $this->getSchoolId();
        $data['author_id']  = auth()->id();
        $data['published_at'] = $data['published_at'] ?? now();

        $announcement = Announcement::create($data);

        if ($alsoEmail && $this->subscribersReachable($request)) {
            // never mailed without a look: the announcement becomes a draft newsletter for someone to check and send
            $draft = app(\App\Newsletter\NewsletterService::class)->draft($this->getSchoolId(), $announcement->title, $announcement->body, $request->user()->id);

            return redirect('/school/website/newsletter/campaigns/'.$draft->id)->with('success', 'Announcement published. Check the email below, then press Send to reach your newsletter subscribers.');
        }

        return back()->with('success', 'Announcement published.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'audience'    => 'required|in:all,class,role',
            'class_id'    => 'nullable|exists:classes,id',
            'target_role' => 'nullable|string|max:50',
            'is_pinned'   => 'boolean',
            'published_at'=> 'nullable|date',
        ]);

        $announcement->update($data);
        return back()->with('success', 'Announcement updated.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    // ── Messages ──────────────────────────────────────────────────

    public function messages(Request $request)
    {
        $sid  = $this->getSchoolId();
        $user = auth()->user();

        $inbox = Message::with('sender:id,name')
            ->where('school_id', $sid)
            ->where('recipient_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'inbox_page')
            ->withQueryString();

        $sent = Message::with('recipient:id,name')
            ->where('school_id', $sid)
            ->where('sender_id', $user->id)
            ->latest()
            ->paginate(20, ['*'], 'sent_page')
            ->withQueryString();

        $users = User::where('school_id', $sid)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('SchoolAdmin/Communication/Messages', [
            'inbox'  => $inbox,
            'sent'   => $sent,
            'users'  => $users,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject'      => 'nullable|string|max:255',
            'body'         => 'required|string|max:5000',
        ]);

        Message::create([
            'school_id'    => $this->getSchoolId(),
            'sender_id'    => auth()->id(),
            'recipient_id' => $data['recipient_id'],
            'subject'      => $data['subject'] ?? null,
            'body'         => $data['body'],
        ]);

        return back()->with('success', 'Message sent.');
    }

    public function readMessage(Message $message)
    {
        if ($message->recipient_id === auth()->id() && !$message->read_at) {
            $message->update(['read_at' => now()]);
        }
        return back();
    }

    // ── Blast ─────────────────────────────────────────────────────

    public function blast(Request $request)
    {
        $sid = $this->getSchoolId();

        return Inertia::render('SchoolAdmin/Communication/Blast', [
            'classes'   => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'audiences' => collect(MessageService::AUDIENCES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'subscribers' => $this->subscribersReachable($request) ? \App\Models\NewsletterSubscriber::where('status', 'confirmed')->count() : null,
            'canSms'    => $request->user()->can('sms.send'),
            'canEmail'  => $request->user()->can('email.send'),
            'smsReady'  => false, // no SMS provider is connected yet
        ]);
    }

    /** Newsletter subscribers can be reached from here only when the extension is on and the school can send email through it */
    private function subscribersReachable(Request $request): bool
    {
        $sid = $this->getSchoolId();

        return $request->user()->can('newsletter.send') && \App\Website\Extensions::enabled($sid, 'newsletter') && \App\Newsletter\NewsletterSettings::resolve($sid) !== null;
    }

    public function sendBlast(Request $request, MessageService $messages, \App\Newsletter\NewsletterService $newsletter)
    {
        if ($request->input('audience') === 'subscribers') {
            // Subscribers get it through the newsletter, so the email has its unsubscribe link and only confirmed people are reached
            abort_unless($this->subscribersReachable($request), 403);
            $data = $request->validate(['channel' => 'required|in:email', 'subject' => 'required|string|max:255', 'message' => 'required|string|max:1600']);
            $school = \App\Models\School::findOrFail($this->getSchoolId());
            $draft = $newsletter->draft($school->id, $data['subject'], $data['message'], $request->user()->id);
            if ($error = $newsletter->start($school, $draft)) {
                return redirect('/school/website/newsletter/campaigns/'.$draft->id)->withErrors(['send' => $error]);
            }

            return redirect('/school/website/newsletter/campaigns/'.$draft->id.'?send=1')->with('success', 'Sending to your newsletter subscribers. Keep this page open until it says it is done.');
        }

        $data = $request->validate([
            'channel'    => 'required|in:sms,email',
            'audience'   => 'required|in:'.implode(',', array_keys(MessageService::AUDIENCES)),
            'class_id'   => ['required_if:audience,class', 'nullable', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())],
            'subject'    => 'required_if:channel,email|nullable|string|max:255',
            'message'    => 'required|string|max:1600',
        ], ['class_id.required_if' => 'Choose the class.']);

        $permission = $data['channel'] === 'sms' ? 'sms.send' : 'email.send';
        abort_unless($request->user()->can($permission), 403);

        $batch = $messages->send($request->user(), $data['channel'], $data['audience'], $data['class_id'] ?? null, $data['subject'] ?? null, $data['message']);

        return redirect("/school/communication/sent/{$batch->id}")->with('success', 'Your message has been sent out. This page shows who it reached.');
    }

    // ── Email Templates ───────────────────────────────────────────

    public function emailTemplates(Request $request)
    {
        $sid = $this->getSchoolId();

        $templates = EmailTemplate::where('school_id', $sid)
            ->orderBy('name')
            ->get();

        return Inertia::render('SchoolAdmin/Communication/EmailTemplates', [
            'templates' => $templates,
        ]);
    }

    public function storeEmailTemplate(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'slug'      => 'required|string|max:100|alpha_dash',
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
        ]);

        $data['school_id'] = $this->getSchoolId();
        EmailTemplate::create($data);

        return back()->with('success', 'Template created.');
    }

    public function updateEmailTemplate(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'subject'   => 'required|string|max:255',
            'body'      => 'required|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update($data);
        return back()->with('success', 'Template updated.');
    }

    // ── Notifications ─────────────────────────────────────────────

    public function notifications(Request $request)
    {
        $notifications = SchoolNotification::where('user_id', auth()->id())
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Communication/Notifications', [
            'notifications' => $notifications,
            'unread_count'  => SchoolNotification::where('user_id', auth()->id())->whereNull('read_at')->count(),
        ]);
    }

    public function markNotificationRead(SchoolNotification $notification)
    {
        if ($notification->user_id === auth()->id()) {
            $notification->markRead();
        }
        return back();
    }

    public function markAllNotificationsRead()
    {
        SchoolNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
