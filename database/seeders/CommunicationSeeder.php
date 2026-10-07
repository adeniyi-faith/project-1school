<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\EmailTemplate;
use App\Models\Message;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (!$school) return;

        $admin   = User::where('school_id', $school->id)->first();
        $allUsers = User::where('school_id', $school->id)->get();
        $classes  = SchoolClass::where('school_id', $school->id)->get();

        if (!$admin) return;

        // ── Announcements ─────────────────────────────────────────────
        $announcements = [
            ['title' => 'Welcome to the New Academic Year 2025-26', 'body' => 'We are thrilled to welcome all students, parents, and staff to the new academic year. Let us make this year productive and successful together.', 'audience' => 'all', 'is_pinned' => true],
            ['title' => 'Annual Sports Day — Save the Date', 'body' => 'The Annual Sports Day will be held on 15th April 2026. All students are encouraged to participate. Registration opens next week.', 'audience' => 'all', 'is_pinned' => false],
            ['title' => 'Fee Payment Deadline Reminder', 'body' => 'This is a reminder that the last date for Q1 fee payment is 10th April 2026. Late payments will attract a penalty.', 'audience' => 'role', 'target_role' => 'accountant', 'is_pinned' => false],
            ['title' => 'Staff Meeting — 5th April', 'body' => 'A mandatory staff meeting has been scheduled for 5th April at 2:00 PM in the conference hall. Attendance is compulsory.', 'audience' => 'role', 'target_role' => 'teacher', 'is_pinned' => true],
        ];

        foreach ($announcements as $a) {
            Announcement::create(array_merge($a, [
                'school_id'    => $school->id,
                'author_id'    => $admin->id,
                'published_at' => now(),
                'class_id'     => null,
            ]));
        }

        // Class-specific announcement
        if ($classes->isNotEmpty()) {
            Announcement::create([
                'school_id'    => $school->id,
                'author_id'    => $admin->id,
                'title'        => 'Class Test Schedule — ' . $classes->first()->name,
                'body'         => 'The upcoming class test for ' . $classes->first()->name . ' is scheduled for 8th April. Topics covered: Chapters 1–5.',
                'audience'     => 'class',
                'class_id'     => $classes->first()->id,
                'is_pinned'    => false,
                'published_at' => now(),
            ]);
        }

        // ── Messages ──────────────────────────────────────────────────
        if ($allUsers->count() >= 2) {
            $users = $allUsers->take(3);
            Message::create([
                'school_id'    => $school->id,
                'sender_id'    => $users[0]->id,
                'recipient_id' => $users[1]->id,
                'subject'      => 'Timetable Update',
                'body'         => 'Please review the updated timetable for next week and confirm your availability.',
            ]);
            Message::create([
                'school_id'    => $school->id,
                'sender_id'    => $users[1]->id,
                'recipient_id' => $users[0]->id,
                'subject'      => 'RE: Timetable Update',
                'body'         => 'Confirmed. I am available for all the assigned slots.',
                'read_at'      => now(),
            ]);
        }

        // ── Email Templates ───────────────────────────────────────────
        $templates = [
            [
                'name'      => 'Fee Reminder',
                'slug'      => 'fee-reminder',
                'subject'   => 'Fee Payment Reminder for {{student_name}}',
                'body'      => "Dear {{parent_name}},\n\nThis is a reminder that the fee of {{amount}} for {{student_name}} (Class: {{class_name}}) is due on {{due_date}}.\n\nPlease make the payment at the earliest to avoid any late charges.\n\nRegards,\n{{school_name}}",
                'variables' => ['student_name', 'parent_name', 'amount', 'class_name', 'due_date', 'school_name'],
            ],
            [
                'name'      => 'Result Published',
                'slug'      => 'result-published',
                'subject'   => 'Exam Results Published — {{exam_name}}',
                'body'      => "Dear {{parent_name}},\n\nThe results for {{exam_name}} have been published. {{student_name}} scored {{total_marks}} marks.\n\nPlease log in to the portal to view the detailed result.\n\nRegards,\n{{school_name}}",
                'variables' => ['parent_name', 'student_name', 'exam_name', 'total_marks', 'school_name'],
            ],
            [
                'name'      => 'Absent Alert',
                'slug'      => 'absent-alert',
                'subject'   => 'Absence Alert — {{student_name}}',
                'body'      => "Dear {{parent_name}},\n\nThis is to inform you that {{student_name}} was marked absent on {{date}}.\n\nIf this is unintentional, please contact the school office.\n\nRegards,\n{{school_name}}",
                'variables' => ['parent_name', 'student_name', 'date', 'school_name'],
            ],
            [
                'name'      => 'Welcome',
                'slug'      => 'welcome',
                'subject'   => 'Welcome to {{school_name}}',
                'body'      => "Dear {{name}},\n\nWelcome to {{school_name}}! Your account has been created.\n\nUsername: {{username}}\nTemporary Password: {{password}}\n\nPlease change your password after first login.\n\nRegards,\n{{school_name}}",
                'variables' => ['name', 'school_name', 'username', 'password'],
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::create(array_merge($t, ['school_id' => $school->id]));
        }

        // ── Notifications ─────────────────────────────────────────────
        if ($admin) {
            $notifs = [
                ['type' => 'fee_overdue',     'title' => 'Fee overdue alert',       'body' => '5 students have overdue fees this month.',   'channel' => 'in-app'],
                ['type' => 'homework_review',  'title' => 'Submission awaiting review', 'body' => '3 homework submissions need your review.','channel' => 'in-app'],
                ['type' => 'leave_request',   'title' => 'Leave request received',  'body' => 'John Doe has submitted a leave request.',     'channel' => 'in-app', 'read_at' => now()],
                ['type' => 'announcement',    'title' => 'New announcement posted', 'body' => 'Sports Day announcement has been published.',  'channel' => 'in-app'],
            ];
            foreach ($notifs as $n) {
                SchoolNotification::create(array_merge($n, [
                    'school_id' => $school->id,
                    'user_id'   => $admin->id,
                ]));
            }
        }
    }
}
