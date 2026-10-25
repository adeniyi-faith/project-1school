<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Message;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Lets a parent write to the school: a message to the office or a child's class teacher, and a
 * quick "my child will be absent" note. Both arrive in the staff Messages inbox (and ring the bell),
 * so staff reply from the place they already look. Replies show on the parent's Messages page.
 */
class ParentContactController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $guardian = $this->guardian();

        if (! $guardian) {
            return Inertia::render('Parent/Messages', ['linked' => false, 'children' => [], 'sent' => [], 'replies' => []]);
        }

        $children = $guardian->students->map(fn (Student $s) => [
            'id'              => $s->id,
            'full_name'       => $s->full_name,
            'class'           => $s->schoolClass?->name,
            'has_teacher'     => $this->classTeacherUserId($s) !== null,
        ])->values();

        $replies = Message::where('school_id', $user->school_id)->where('recipient_id', $user->id)
            ->with('sender:id,name')->latest()->limit(30)->get();
        $page = [
            'linked'   => true,
            'children' => $children,
            'sent'     => Message::where('school_id', $user->school_id)->where('sender_id', $user->id)
                ->with('recipient:id,name')->latest()->limit(30)->get()
                ->map(fn ($m) => [
                    'id' => $m->id, 'subject' => $m->subject, 'body' => $m->body,
                    'to' => $m->recipient?->name, 'when' => $m->created_at?->diffForHumans(),
                ]),
            'replies'  => $replies->map(fn ($m) => [
                'id' => $m->id, 'subject' => $m->subject, 'body' => $m->body,
                'from' => $m->sender?->name, 'when' => $m->created_at?->diffForHumans(),
                'unread' => $m->read_at === null,
            ]),
        ];

        // Opening the page counts as reading the replies (the page above still shows them as new)
        Message::whereIn('id', $replies->whereNull('read_at')->pluck('id'))->update(['read_at' => now()]);

        return Inertia::render('Parent/Messages', $page);
    }

    public function send(Request $request)
    {
        $guardian = $this->guardian();
        abort_unless($guardian, 403);

        $data = $request->validate([
            'student_id' => 'required|integer',
            'to'         => 'required|in:office,teacher',
            'subject'    => 'nullable|string|max:150',
            'body'       => 'required|string|max:3000',
        ]);

        $student = $this->child($guardian, $data['student_id']);

        $recipients = $data['to'] === 'teacher'
            ? collect([$this->classTeacherUserId($student)])->filter()->values()
            : $this->officeUserIds($guardian->school_id);

        if ($recipients->isEmpty()) {
            return back()->withErrors(['to' => $data['to'] === 'teacher'
                ? "{$student->full_name}'s class has no class teacher set yet. Please write to the office instead."
                : 'The school has not set up anyone to receive messages yet. Please phone the office.']);
        }

        $subject = trim(($data['subject'] ?? '') ?: 'Message from a parent') . " (about {$student->full_name})";
        $this->deliver($guardian, $recipients, $subject, $data['body']);

        return back()->with('success', 'Message sent. The school will reply here.');
    }

    public function absent(Request $request)
    {
        $guardian = $this->guardian();
        abort_unless($guardian, 403);

        $data = $request->validate([
            'student_id' => 'required|integer',
            'date'       => 'required|date|after_or_equal:today|before:+120 days',
            'reason'     => 'nullable|string|max:1000',
        ]);

        $student = $this->child($guardian, $data['student_id']);
        $day     = Carbon::parse($data['date'])->format('D d M Y');

        $recipients = $this->officeUserIds($guardian->school_id)
            ->merge([$this->classTeacherUserId($student)])->filter()->unique()->values();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['date' => 'The school has not set up anyone to receive messages yet. Please phone the office.']);
        }

        $this->deliver(
            $guardian,
            $recipients,
            "Absent note: {$student->full_name} on {$day}",
            trim("{$student->full_name} ({$student->schoolClass?->name}) will be absent on {$day}.\n" . ($data['reason'] ?? '')),
        );

        return back()->with('success', 'Thank you. The school has been told.');
    }

    /** One message (and one bell notice) per staff member who should see it. */
    private function deliver(Guardian $guardian, $recipientIds, string $subject, string $body): void
    {
        $sender = auth()->user();

        foreach ($recipientIds as $id) {
            Message::create([
                'school_id' => $guardian->school_id, 'sender_id' => $sender->id, 'recipient_id' => $id,
                'subject' => $subject, 'body' => $body,
            ]);
            SchoolNotification::create([
                'school_id' => $guardian->school_id, 'user_id' => $id, 'type' => 'parent_message',
                'title' => $subject, 'body' => "From {$guardian->name}", 'channel' => 'in-app',
                'data' => ['url' => '/school/communication/messages'],
            ]);
        }
    }

    private function guardian(): ?Guardian
    {
        $user = auth()->user();

        return Guardian::with('students.schoolClass:id,name,class_teacher_id,class_teacher_staff_id')
            ->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
    }

    /** A parent can only write about their own children. */
    private function child(Guardian $guardian, int $studentId): Student
    {
        return $guardian->students->firstWhere('id', $studentId) ?? abort(404);
    }

    private function classTeacherUserId(Student $student): ?int
    {
        $class = $student->schoolClass;

        return $class?->classTeacher?->user_id ?? $class?->class_teacher_id;
    }

    private function officeUserIds(int $schoolId)
    {
        return User::role('school-admin')->where('school_id', $schoolId)->where('status', 'active')->pluck('id');
    }
}
