<?php

namespace Database\Seeders;

use App\Models\CbtAttempt;
use App\Models\CbtTest;
use App\Models\Student;
use App\Models\User;
use App\Services\CbtService;
use App\Services\CertificateService;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Services\IdCardService;
use App\Services\InstalmentService;
use App\Support\DemoSchool;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fills the screens that were empty in the public demo school (Heritage Crown Academy, Lagos):
 * staff leave and payroll, shifts, messages, scholarships and payment plans, houses and clubs,
 * syllabi and online classes, lesson notes and schemes of work, question banks, computer tests
 * with finished sittings, printed exam papers, report card comments and ID cards.
 *
 * It only touches its own tables, so it can be run again at any time without disturbing
 * students, fees or results.  `demo:fill` runs it on every deploy after the other seeders.
 */
class DemoRichSeeder extends Seeder
{
    private int $sid;
    private Carbon $now;
    private ?int $adminId = null;
    private array $classes = [];      // name => id
    private array $subjects = [];     // name => id
    private array $teachers = [];     // staff ids of teachers
    private array $termId = [];       // current term id
    private ?int $termIdValue = null;

    public function run(): void
    {
        if (! config('app.demo_enabled')) {
            return;
        }

        $school = DemoSchool::school();
        $this->sid = $school->id;
        $this->now = Carbon::now();

        $students = DB::table('students')->where('school_id', $this->sid)->where('status', 'active')->orderBy('id')->get();
        $staff = DB::table('staff')->where('school_id', $this->sid)->orderBy('id')->get();
        $admin = User::where('school_id', $this->sid)->where('email', config('app.demo_email', env('DEMO_EMAIL', 'demo@example.com')))->first()
            ?? User::where('school_id', $this->sid)->orderBy('id')->first();
        if ($students->isEmpty() || $staff->isEmpty() || ! $admin) {
            return;
        }
        $this->adminId = $admin->id;
        Auth::setUser($admin);

        $this->classes = DB::table('classes')->where('school_id', $this->sid)->pluck('id', 'name')->all();
        $this->subjects = DB::table('subjects')->where('school_id', $this->sid)->whereNull('deleted_at')->pluck('id', 'name')->all();
        $this->termIdValue = DB::table('terms')->where('school_id', $this->sid)->where('is_current', true)->value('id');
        $this->teachers = DB::table('staff')->join('designations', 'designations.id', '=', 'staff.designation_id')
            ->where('staff.school_id', $this->sid)->where('designations.name', 'like', '%Teacher%')->pluck('staff.id')->all() ?: $staff->pluck('id')->all();

        mt_srand(20261012);

        DB::transaction(function () use ($students, $staff, $admin) {
            $this->wipe();
            $this->setup();
            $this->hr($staff);
            $this->messages($admin);
            $this->library($students);
            $this->classwork($students);
            $this->feesExtras($students);
            $this->housesAndClubs($students, $staff);
            $this->lessonNotes($admin);
            $this->questionBanks($admin);
            $this->examPapers($admin);
            $this->reportCards($admin);
        });
        // These use the real services (each does its own transaction)
        $this->cbtTests($admin, $students);
        $this->idCardsAndCertificates($admin, $students);
        $this->instalments($admin);

        $this->command?->info('Demo rich data filled: staff leave and payroll, messages, houses, clubs, lesson notes, question banks, CBT tests, exam papers, comments and ID cards.');
    }

    // ───────────────────────── helpers ─────────────────────────

    private function put(string $table, array $rows): void
    {
        $t = $this->now->toDateTimeString();
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table($table)->insert(array_map(function ($r) use ($t, $table) {
                $r += ['school_id' => $this->sid];
                if (! Schema::hasColumn($table, 'school_id')) {
                    unset($r['school_id']);
                }
                foreach ($r as $k => $v) {
                    if (is_array($v)) {
                        $r[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    }
                }

                return $r + ['created_at' => $t, 'updated_at' => $t];
            }, $chunk));
        }
    }

    private function putGetId(string $table, array $row): int
    {
        $t = $this->now->toDateTimeString();
        $row += ['school_id' => $this->sid];
        foreach ($row as $k => $v) {
            if (is_array($v)) {
                $row[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }

        return (int) DB::table($table)->insertGetId($row + ['created_at' => $t, 'updated_at' => $t]);
    }

    private function pick(array $list): mixed
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    private function teacher(): int
    {
        return $this->pick($this->teachers);
    }

    private function wipe(): void
    {
        $sid = $this->sid;
        $tests = DB::table('cbt_tests')->where('school_id', $sid)->pluck('id');
        if ($tests->isNotEmpty()) {
            DB::table('cbt_test_sections')->whereIn('cbt_test_id', $tests)->delete();
        }
        foreach ([
            'cbt_attempts', 'cbt_candidates', 'cbt_tests', 'cbt_questions', 'cbt_banks', 'exam_papers',
            'lesson_plans', 'schemes_of_work', 'lesson_note_formats', 'report_card_remarks', 'report_card_comment_bank',
            'student_certificates', 'student_id_cards', 'id_card_designs', 'certificate_templates',
            'house_points', 'club_members', 'clubs', 'student_scholarships', 'scholarships', 'payment_plans',
            'homework_submissions', 'online_classes', 'syllabi', 'book_reservations', 'messages', 'message_deliveries', 'message_batches',
            'payrolls', 'salary_structures', 'leave_requests', 'leave_types', 'email_templates', 'shifts', 'houses',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                DB::table($table)->where('school_id', $sid)->delete();
            }
        }
        DB::table('students')->where('school_id', $sid)->update(['house_id' => null]);
        DB::table('invoice_instalments')->where('school_id', $sid)->delete();
        DB::table('invoices')->where('school_id', $sid)->whereNotNull('payment_plan_id')->update(['payment_plan_id' => null]);
    }

    // ───────────────────────── school setup ─────────────────────────

    private function setup(): void
    {
        $this->put('shifts', [
            ['name' => 'Morning (lessons)', 'start_time' => '07:45', 'end_time' => '14:30'],
            ['name' => 'Early Years', 'start_time' => '08:00', 'end_time' => '13:00'],
            ['name' => 'Extra lessons', 'start_time' => '15:00', 'end_time' => '17:00'],
            ['name' => 'Boarding prep', 'start_time' => '18:30', 'end_time' => '20:30'],
        ]);

        $this->put('email_templates', [
            ['name' => 'Fee reminder', 'slug' => 'fee-reminder', 'subject' => 'School fees reminder for {student_name}', 'body' => "Dear {parent_name},\n\nThis is a friendly reminder that {student_name} still has ₦{amount_due} outstanding on school fees. Kindly pay on or before {due_date}, online from the parent portal or at the bursar's office.\n\nThank you,\n{school_name}", 'variables' => ['student_name', 'parent_name', 'amount_due', 'due_date', 'school_name'], 'is_active' => true],
            ['name' => 'Results published', 'slug' => 'results-published', 'subject' => '{term} results are ready', 'body' => "Dear {parent_name},\n\nThe {term} results for {student_name} are now available on the parent portal, where you can also download the report card.\n\n{school_name}", 'variables' => ['term', 'student_name', 'parent_name', 'school_name'], 'is_active' => true],
            ['name' => 'Welcome to the school', 'slug' => 'welcome', 'subject' => 'Welcome to {school_name}', 'body' => "Dear {parent_name},\n\nWe are delighted to welcome {student_name} to {school_name}. Resumption is on {resumption_date}. Please bring the items on the admission checklist.\n\nWe look forward to a great year together.", 'variables' => ['parent_name', 'student_name', 'school_name', 'resumption_date'], 'is_active' => true],
            ['name' => 'Parents’ meeting', 'slug' => 'parents-meeting', 'subject' => 'Parents’ meeting on {date}', 'body' => "Dear {parent_name},\n\nYou are invited to the termly parents’ meeting on {date} at {time} in the school hall. Please come with your child’s report book.\n\n{school_name}", 'variables' => ['parent_name', 'date', 'time', 'school_name'], 'is_active' => true],
        ]);
    }

    // ───────────────────────── staff leave and payroll ─────────────────────────

    private function hr($staff): void
    {
        $types = [];
        foreach ([['Annual leave', 20, true, 'Yearly rest for every full-time staff member.'], ['Sick leave', 10, true, 'Medical report needed after two days.'], ['Maternity leave', 90, true, 'For nursing mothers.'], ['Paternity leave', 10, true, null], ['Compassionate leave', 5, true, 'Bereavement or family emergency.'], ['Unpaid leave', 30, false, 'Agreed with the Principal.']] as [$name, $days, $paid, $desc]) {
            $types[$name] = $this->putGetId('leave_types', ['name' => $name, 'max_days_per_year' => $days, 'is_paid' => $paid, 'description' => $desc, 'is_active' => true]);
        }

        $reasons = ['Family wedding in Ibadan', 'Medical check-up', 'Travelling for the NYSC passing-out of a sibling', 'Recovering from malaria', 'Moving house', 'Attending a teachers’ training workshop', 'Bereavement in the family', 'Rest after the exams'];
        $typeNames = array_keys($types);
        foreach ($staff->shuffle()->take(18) as $i => $member) {
            $start = Carbon::now()->subDays(mt_rand(-20, 80))->startOfDay();
            $days = mt_rand(1, 9);
            $status = $start->isFuture() ? $this->pick(['pending', 'pending', 'approved']) : $this->pick(['approved', 'approved', 'rejected']);
            $this->put('leave_requests', [[
                'staff_id' => $member->id, 'leave_type_id' => $types[$this->pick(array_slice($typeNames, 0, 5))],
                'start_date' => $start->toDateString(), 'end_date' => $start->copy()->addDays($days - 1)->toDateString(), 'days' => $days,
                'reason' => $this->pick($reasons), 'status' => $status,
                'approved_by' => $status === 'pending' ? null : $this->adminId,
                'approval_note' => $status === 'rejected' ? 'Please move this to the holiday period; exams are on.' : ($status === 'approved' ? 'Approved. Arrange cover with your head of department.' : null),
                'actioned_at' => $status === 'pending' ? null : $start->copy()->subDays(3)->toDateTimeString(),
            ]]);
        }

        // Pay structure for everyone, and three paid months
        $months = [Carbon::now()->subMonths(3)->format('Y-m'), Carbon::now()->subMonths(2)->format('Y-m'), Carbon::now()->subMonths(1)->format('Y-m')];
        foreach ($staff as $member) {
            $basic = (float) ($member->salary ?: mt_rand(120, 300) * 1000);
            $allow = [['label' => 'Housing', 'amount' => round($basic * 0.2)], ['label' => 'Transport', 'amount' => round($basic * 0.1)]];
            $deduct = [['label' => 'Pension (8%)', 'amount' => round($basic * 0.08)], ['label' => 'PAYE tax', 'amount' => round($basic * 0.05)]];
            $this->put('salary_structures', [['staff_id' => $member->id, 'basic_salary' => $basic, 'allowances' => $allow, 'deductions' => $deduct, 'is_active' => true]]);
            $totalAllow = array_sum(array_column($allow, 'amount'));
            $totalDeduct = array_sum(array_column($deduct, 'amount'));
            foreach ($months as $m => $month) {
                $present = mt_rand(18, 22);
                $this->put('payrolls', [[
                    'staff_id' => $member->id, 'month_year' => $month, 'basic_salary' => $basic, 'total_allowances' => $totalAllow, 'total_deductions' => $totalDeduct,
                    'net_salary' => $basic + $totalAllow - $totalDeduct, 'working_days' => 22, 'present_days' => $present, 'leave_days' => 22 - $present,
                    'allowances_snapshot' => $allow, 'deductions_snapshot' => $deduct, 'status' => 'paid',
                    'paid_on' => Carbon::createFromFormat('Y-m-d', $month.'-26')->toDateString(), 'note' => null,
                ]]);
            }
        }
    }

    // ───────────────────────── messages ─────────────────────────

    private function messages(User $admin): void
    {
        $others = User::where('school_id', $this->sid)->where('id', '!=', $admin->id)->orderBy('id')->take(10)->get();
        $threads = [
            ['Resumption arrangements', "Good morning. Please confirm that the timetable for the new term is ready so we can send it to parents on Friday."],
            ['Excursion to the National Museum', "The JSS 2 trip to the National Museum in Onikan is approved for next Thursday. Please send the list of students and the permission letters."],
            ['Library books for the new term', "We need 40 more copies of the English textbook for JSS 1. Can the budget cover this?"],
            ['Fee clearance for exam week', "Please send me the list of students with outstanding fees before the exams begin."],
            ['Staff meeting', "There will be a short staff meeting on Wednesday at 2:30 pm in the staff room. Agenda: continuous assessment and report cards."],
            ['Bus route change', "The Ikeja route will pass through Allen Avenue from Monday because of the road repairs."],
        ];
        $i = 0;
        foreach ($others as $other) {
            $thread = $threads[$i % count($threads)];
            $when = Carbon::now()->subHours(mt_rand(2, 120));
            $this->put('messages', [
                ['sender_id' => $other->id, 'recipient_id' => $admin->id, 'subject' => $thread[0], 'body' => $thread[1], 'read_at' => $i % 3 === 0 ? null : $when->copy()->addHour()->toDateTimeString(), 'created_at' => $when->toDateTimeString()],
                ['sender_id' => $admin->id, 'recipient_id' => $other->id, 'subject' => 'Re: '.$thread[0], 'body' => 'Thank you. Noted, and I will follow it up today.', 'read_at' => $i % 2 === 0 ? $when->copy()->addHours(3)->toDateTimeString() : null, 'created_at' => $when->copy()->addHours(2)->toDateTimeString()],
            ]);
            $i++;
        }

        // A couple of sent bulk messages, so the "Sent messages" page has lines to show
        $parents = DB::table('guardians')->where('school_id', $this->sid)->whereNotNull('phone')->orderBy('id')->take(12)->get();
        foreach ([
            ['sms', 'Reminder: Second term fees close on Friday. Pay online from the parent portal. - Heritage Crown Academy', 'all parents'],
            ['email', 'Dear parent, the termly parents’ meeting holds this Saturday at 10 am in the school hall. Kindly attend.', 'all parents'],
        ] as $b => [$channel, $text, $audience]) {
            $batchId = $this->putGetId('message_batches', [
                'channel' => $channel, 'audience' => $audience, 'subject' => $channel === 'email' ? 'Parents’ meeting' : null, 'body' => $text,
                'sent_by' => $this->adminId,
            ]);
            foreach ($parents as $k => $g) {
                $state = $k === 0 ? 'failed' : ($k === 1 ? 'not_sent' : 'sent');
                $this->put('message_deliveries', [[
                    'message_batch_id' => $batchId, 'recipient_name' => $g->name, 'recipient_type' => 'parent', 'recipient_id' => $g->id, 'attempts' => $state === 'not_sent' ? 0 : 1,
                    'to' => $channel === 'sms' ? $g->phone : ($g->email ?: 'parent'.$g->id.'@example.com'), 'status' => $state,
                    'error' => $state === 'failed' ? 'No SMS provider is set up yet' : ($state === 'not_sent' ? 'No phone number on record' : null),
                    'sent_at' => $state === 'sent' ? Carbon::now()->subDays(2 + $b)->toDateTimeString() : null,
                ]]);
            }
        }
    }

    // ───────────────────────── library ─────────────────────────

    private function library($students): void
    {
        $books = DB::table('books')->where('school_id', $this->sid)->take(6)->pluck('id')->all();
        foreach ($students->shuffle()->take(6) as $i => $s) {
            if (isset($books[$i])) {
                $this->put('book_reservations', [['book_id' => $books[$i], 'member_type' => 'student', 'member_id' => $s->id, 'reserved_date' => Carbon::now()->subDays(mt_rand(0, 6))->toDateString(), 'status' => 'waiting']]);
            }
        }
    }

    // ───────────────────────── syllabi, online classes, homework answers ─────────────────────────

    private function classwork($students): void
    {
        $year = DB::table('academic_years')->where('school_id', $this->sid)->where('is_current', true)->value('name') ?? '2026/2027';
        $topicsBy = [
            'Mathematics' => ['Whole numbers and place value', 'Fractions and decimals', 'Percentages', 'Algebraic expressions', 'Linear equations', 'Angles and triangles', 'Perimeter and area', 'Statistics: mean, median and mode'],
            'English Language' => ['Parts of speech', 'Tenses', 'Comprehension passages', 'Letter writing', 'Narrative essays', 'Summary writing', 'Idioms and proverbs'],
            'Intermediate Science' => ['Living and non-living things', 'The cell', 'States of matter', 'Photosynthesis', 'Energy and its forms', 'Electricity', 'The human body systems'],
            'Digital Technologies' => ['Parts of the computer', 'Operating systems', 'Word processing', 'Spreadsheets', 'The internet and email', 'Staying safe online'],
            'Social and Citizenship Studies' => ['National symbols', 'Rights and duties', 'The three arms of government', 'Nigerian cultures', 'Leadership and followership'],
            'Business Studies' => ['Meaning of business', 'Trade and commerce', 'Book-keeping basics', 'The cash book', 'Banking', 'Office practice'],
        ];
        $n = 0;
        foreach (['JSS 1', 'JSS 2', 'JSS 3'] as $className) {
            $classId = $this->classes[$className] ?? null;
            if (! $classId) {
                continue;
            }
            foreach ($topicsBy as $subject => $topics) {
                $sid = $this->subjects[$subject] ?? null;
                if (! $sid) {
                    continue;
                }
                $covered = mt_rand(2, count($topics) - 1);
                $this->put('syllabi', [[
                    'class_id' => $classId, 'subject_id' => $sid, 'academic_year' => $year, 'title' => $subject.' syllabus, '.$className,
                    'topics' => array_map(fn ($t, $k) => ['title' => $t, 'covered' => $k < $covered], $topics, array_keys($topics)),
                    'completion_percent' => (int) round($covered / count($topics) * 100),
                ]]);
                if ($n++ < 12) {
                    $this->put('online_classes', [[
                        'class_id' => $classId, 'subject_id' => $sid, 'teacher_id' => $this->teacher(), 'title' => $subject.' revision class ('.$className.')',
                        'platform' => $this->pick(['zoom', 'google_meet', 'zoom']), 'meeting_url' => 'https://meet.example.com/'.strtolower(str_replace(' ', '-', $className.'-'.$subject)),
                        'meeting_id' => (string) mt_rand(100, 999).' '.mt_rand(1000, 9999).' '.mt_rand(100, 999), 'passcode' => (string) mt_rand(100000, 999999),
                        'scheduled_at' => Carbon::now()->addDays(mt_rand(-6, 9))->setTime($this->pick([16, 17, 18, 19]), 0)->toDateTimeString(),
                        'duration_minutes' => $this->pick([40, 45, 60]), 'status' => $n % 4 === 0 ? 'completed' : 'scheduled',
                    ]]);
                }
            }
        }

        // Pupils' answers to homework already set
        $answers = ['I have finished the work in my notebook. Please check it.', 'Done, and I added two more examples of my own.', 'This is my answer. I found question 3 hard but I tried.', 'I did it with my elder sister’s help. Here is my working.'];
        $hw = DB::table('homework')->where('school_id', $this->sid)->orderByDesc('id')->take(14)->get();
        foreach ($hw as $h) {
            $pupils = $students->where('class_id', $h->class_id)->shuffle()->take(mt_rand(5, 9));
            foreach ($pupils as $k => $p) {
                $marked = $k % 3 === 0;
                $this->put('homework_submissions', [[
                    'homework_id' => $h->id, 'student_id' => $p->id, 'text_response' => $this->pick($answers),
                    'status' => $marked ? 'reviewed' : 'submitted', 'teacher_remarks' => $marked ? $this->pick(['Very good work.', 'Neat. Check your spelling.', 'Well done, keep it up.', 'Good effort; see me for corrections.']) : null,
                ]]);
            }
        }
    }

    // ───────────────────────── scholarships and payment plans ─────────────────────────

    private function feesExtras($students): void
    {
        $ids = [];
        foreach ([
            ['Staff child discount', 'percent', 50, 'Children of staff pay half fees.'],
            ['Sibling discount', 'percent', 10, 'For the second and later child of the same parent.'],
            ['Academic excellence award', 'fixed', 50000, 'Top pupil in each class each year.'],
            ['Old pupils’ bursary', 'fixed', 30000, 'Support from the alumni association.'],
        ] as [$name, $type, $value, $desc]) {
            $ids[$name] = $this->putGetId('scholarships', ['name' => $name, 'type' => $type, 'value' => $value, 'description' => $desc, 'is_active' => true]);
        }
        foreach ($students->shuffle()->take(12) as $i => $s) {
            $this->put('student_scholarships', [[
                'student_id' => $s->id, 'scholarship_id' => array_values($ids)[$i % 4], 'approved_by' => $this->adminId,
                'approved_at' => Carbon::now()->subDays(mt_rand(5, 60))->toDateTimeString(), 'note' => $this->pick(['Approved by the Principal.', 'Recommended by the class teacher.', 'Parent applied in writing.']),
            ]]);
        }

        $this->put('payment_plans', [
            ['name' => 'Two instalments', 'description' => 'Most of the fee at resumption, the rest by mid-term.', 'parts' => [['label' => 'At resumption', 'percent' => 60], ['label' => 'By mid-term', 'percent' => 40]], 'is_active' => true],
            ['name' => 'Three instalments', 'description' => 'For families who prefer smaller payments.', 'parts' => [['label' => 'At resumption', 'percent' => 50], ['label' => 'Mid-term', 'percent' => 30], ['label' => 'Before exams', 'percent' => 20]], 'is_active' => true],
        ]);
    }

    // ───────────────────────── houses and clubs ─────────────────────────

    private function housesAndClubs($students, $staff): void
    {
        $senior = $students->filter(fn ($s) => in_array($s->class_id, array_filter([$this->classes['SS 3'] ?? 0, $this->classes['SS 2'] ?? 0, $this->classes['JSS 3'] ?? 0]), true))->values();
        $houses = [
            ['Gold House', '#d4a017', 'Strive for excellence'], ['Blue House', '#1d4ed8', 'Loyalty and honour'],
            ['Green House', '#15803d', 'Growth through effort'], ['Red House', '#b91c1c', 'Courage in all things'],
        ];
        $houseIds = [];
        foreach ($houses as $k => [$name, $colour, $motto]) {
            $captain = $senior->get($k * 3);
            $houseIds[] = $this->putGetId('houses', ['name' => $name, 'colour' => $colour, 'motto' => $motto, 'patron_id' => $this->teachers[$k % count($this->teachers)], 'captain_id' => $captain?->id, 'is_active' => true]);
        }
        foreach ($students->values() as $i => $s) {
            DB::table('students')->where('id', $s->id)->update(['house_id' => $houseIds[$i % 4]]);
        }
        $events = [['Inter-house sports', 'Won the 4 × 100 m relay'], ['Inter-house sports', 'Best matched-past'], ['Spelling bee', 'Second place'], ['Quiz competition', 'Winning team'], ['Clean house award', 'Neatest house for the week'], ['Debate', 'Best speaker'], ['Inter-house sports', 'Tug of war winners'], ['Attendance', 'Best attendance for the month']];
        foreach ($events as $e => [$event, $reason]) {
            foreach ($houseIds as $h => $hid) {
                $this->put('house_points', [['house_id' => $hid, 'points' => mt_rand(5, 25) * ($e % 7 === 6 ? -1 : 1), 'reason' => $reason, 'event' => $event, 'awarded_on' => Carbon::now()->subDays(mt_rand(1, 50))->toDateString(), 'awarded_by' => $this->adminId]]);
            }
        }

        $clubs = [['Jet Club (science and technology)', 'Experiments, robotics and the science fair.', 'Fridays, 2:30 pm'], ['Literary and Debating Society', 'Debates, quizzes, poetry and public speaking.', 'Wednesdays, 2:30 pm'], ['Press Club', 'Writes and prints the school magazine.', 'Tuesdays, 2:30 pm'], ['Music and Drama Club', 'Choir, drama and the end-of-year play.', 'Thursdays, 2:30 pm'], ['Red Cross Society', 'First aid and community service.', 'Mondays, 2:30 pm'], ['Chess and Ayo Club', 'Games that sharpen the mind.', 'Fridays, 1:30 pm']];
        foreach ($clubs as $c => [$name, $desc, $meets]) {
            $clubId = $this->putGetId('clubs', ['name' => $name, 'description' => $desc, 'patron_id' => $this->teachers[($c + 4) % count($this->teachers)], 'meets' => $meets, 'is_active' => true]);
            $members = $students->filter(fn ($s) => $s->class_id >= ($this->classes['Primary 4'] ?? 0))->shuffle()->take(mt_rand(10, 18))->values();
            foreach ($members as $m => $s) {
                $this->put('club_members', [['club_id' => $clubId, 'student_id' => $s->id, 'role' => $m === 0 ? 'President' : ($m === 1 ? 'Secretary' : ($m === 2 ? 'Treasurer' : 'Member')), 'joined_on' => Carbon::now()->subDays(mt_rand(10, 200))->toDateString()]]);
            }
        }
    }

    // ───────────────────────── schemes of work and lesson notes ─────────────────────────

    private function lessonNotes(User $admin): void
    {
        \App\Models\LessonNoteFormat::ensureFor($this->sid);
        $formats = DB::table('lesson_note_formats')->where('school_id', $this->sid)->get();
        $standard = $formats->firstWhere('is_default', 1) ?? $formats->first();
        $five = $formats->firstWhere('name', '5E lesson plan') ?? $standard;
        $fill = function ($format, array $texts): array {
            $out = [];
            foreach (json_decode($format->sections, true) as $i => $sec) {
                $out[$sec['key']] = $texts[$i] ?? $texts[array_key_last($texts)];
            }

            return $out;
        };

        $schemes = [
            ['JSS 1', 'Mathematics', [['Whole numbers: place value and counting', 'Reading and writing numbers up to millions; ordering and comparing.'], ['Whole numbers: operations', 'Addition, subtraction, multiplication and division with word problems.'], ['Factors and multiples', 'Prime numbers, HCF and LCM.'], ['Fractions', 'Equivalent fractions, adding and subtracting fractions.'], ['Decimals and percentages', 'Converting between fractions, decimals and percentages.'], ['Introduction to algebra', 'Letters for numbers; simple expressions.'], ['Linear equations', 'Solving one-step and two-step equations.'], ['Revision and test', 'Revision exercises and a short class test.']]],
            ['JSS 1', 'English Language', [['Nouns and pronouns', 'Types of nouns; using pronouns correctly.'], ['Verbs and tenses', 'Simple present, past and future.'], ['Adjectives and adverbs', 'Describing people, places and actions.'], ['Comprehension', 'Reading a passage and answering questions.'], ['Letter writing', 'Informal letters: format and content.'], ['Narrative essay', 'Planning and writing a story.']]],
            ['JSS 1', 'Intermediate Science', [['Living and non-living things', 'Seven characteristics of living things.'], ['The cell', 'Parts of plant and animal cells.'], ['States of matter', 'Solids, liquids and gases; changes of state.'], ['Mixtures and separation', 'Filtration, evaporation and decantation.'], ['Energy', 'Forms and sources of energy.'], ['Photosynthesis', 'How plants make their food.']]],
            ['JSS 2', 'Digital Technologies', [['Computer hardware', 'Input, output, processing and storage.'], ['Operating systems', 'Windows and files and folders.'], ['Word processing', 'Typing, formatting and saving documents.'], ['Spreadsheets', 'Cells, rows, columns and simple formulas.'], ['Internet and email', 'Browsers, search engines and email.'], ['Online safety', 'Passwords, scams and cyber-bullying.']]],
            ['JSS 2', 'Social and Citizenship Studies', [['National symbols', 'The flag, coat of arms, anthem and pledge.'], ['Rights and responsibilities', 'Human rights and duties of citizens.'], ['Arms of government', 'Legislature, executive and judiciary.'], ['Nigerian cultures', 'Major ethnic groups and festivals.'], ['Leadership', 'Qualities of good leaders.']]],
            ['JSS 3', 'Business Studies', [['Meaning of business', 'Types of business and why people trade.'], ['Trade and commerce', 'Home trade and foreign trade.'], ['Book-keeping', 'Documents and the cash book.'], ['Banking', 'Types of accounts, cheques and ATMs.'], ['Office practice', 'Records, memos and filing.']]],
        ];
        $statuses = ['approved', 'approved', 'submitted', 'draft', 'approved', 'rejected'];
        $k = 0;
        foreach ($schemes as [$className, $subject, $weeks]) {
            $classId = $this->classes[$className] ?? null;
            $subjectId = $this->subjects[$subject] ?? null;
            if (! $classId || ! $subjectId) {
                continue;
            }
            $schemeId = $this->putGetId('schemes_of_work', [
                'class_id' => $classId, 'subject_id' => $subjectId, 'term_id' => $this->termIdValue, 'title' => $subject.' scheme of work, '.$className.', first term',
                'weeks' => array_map(fn ($w, $i) => ['week' => $i + 1, 'topic' => $w[0], 'details' => $w[1]], $weeks, array_keys($weeks)), 'created_by' => $admin->id,
            ]);
            foreach (array_slice($weeks, 0, 4) as $i => [$topic, $details]) {
                $format = $i % 3 === 2 ? $five : $standard;
                $status = $statuses[($k + $i) % count($statuses)];
                $texts = [
                    "By the end of the lesson, pupils should be able to explain {$topic} in their own words and solve or answer simple questions on it.",
                    "The teacher revises the last lesson with three quick questions and introduces {$topic} with an example from everyday life in Lagos.",
                    "{$details} The teacher explains with examples on the board and pupils copy the key points.",
                    "Pupils work in pairs on the class exercise while the teacher moves round to help. Two pupils present their answers.",
                    "The teacher summarises the main points and sets a short homework: five questions on {$topic}.",
                ];
                $this->put('lesson_plans', [[
                    'class_id' => $classId, 'subject_id' => $subjectId, 'teacher_id' => $this->teacher(), 'title' => $topic,
                    'week_start' => Carbon::now()->startOfWeek()->subWeeks(4 - $i)->toDateString(), 'status' => $status,
                    'reviewer_feedback' => $status === 'rejected' ? 'Please add an activity for pupils and the resources to be used.' : null,
                    'reviewed_by' => in_array($status, ['approved', 'rejected'], true) ? $admin->id : null,
                    'reviewed_at' => in_array($status, ['approved', 'rejected'], true) ? Carbon::now()->subDays(mt_rand(1, 20))->toDateTimeString() : null,
                    'term_id' => $this->termIdValue, 'scheme_of_work_id' => $schemeId, 'lesson_note_format_id' => $format->id, 'week' => $i + 1, 'duration_minutes' => 40,
                    'sections' => $fill($format, $texts), 'ai_drafted' => $i === 1, 'created_by' => $admin->id,
                ]]);
            }
            $k++;
        }
    }

    // ───────────────────────── question banks ─────────────────────────

    private function questionBanks(User $admin): void
    {
        $data = require database_path('seeders/data/demo_questions.php');
        foreach ($data as $bankName => [$subject, $description, $questions]) {
            $bankId = $this->putGetId('cbt_banks', ['name' => $bankName, 'subject_id' => $this->subjects[$subject] ?? null, 'description' => $description, 'created_by' => $admin->id]);
            foreach ($questions as $q) {
                if (is_int($q[3])) {
                    [$topic, $difficulty, $text, $marks, $guide] = $q;
                    $this->put('cbt_questions', [['cbt_bank_id' => $bankId, 'topic' => $topic, 'difficulty' => $difficulty, 'text' => $text, 'options' => null, 'correct_option' => null, 'explanation' => null, 'marks' => $marks, 'created_by' => $admin->id, 'type' => 'theory', 'marking_guide' => $guide]]);
                } else {
                    [$topic, $difficulty, $text, $options, $right, $why] = $q;
                    $this->put('cbt_questions', [['cbt_bank_id' => $bankId, 'topic' => $topic, 'difficulty' => $difficulty, 'text' => $text, 'options' => $options, 'correct_option' => $right, 'explanation' => $why, 'marks' => 1, 'created_by' => $admin->id, 'type' => 'objective', 'marking_guide' => null]]);
                }
            }
        }
    }

    // ───────────────────────── printed exam papers ─────────────────────────

    private function examPapers(User $admin): void
    {
        $specs = [
            ['First Term Examination: Mathematics', 'JSS 1', 'Mathematics', 'JSS Mathematics', 90],
            ['First Term Examination: English Language', 'JSS 1', 'English Language', 'English Language', 120],
            ['First Term Examination: Intermediate Science', 'JSS 2', 'Intermediate Science', 'Intermediate Science', 90],
            ['First Term Examination: Business Studies', 'JSS 3', 'Business Studies', 'Business Studies', 60],
            ['Mock Examination: Physics', 'SS 3', 'Physics', 'Physics', 120],
            ['First Term Examination: Economics', 'SS 2', 'Economics', 'Economics', 90],
        ];
        foreach ($specs as [$title, $className, $subject, $bankName, $minutes]) {
            $bank = DB::table('cbt_banks')->where('school_id', $this->sid)->where('name', $bankName)->value('id');
            if (! $bank) {
                continue;
            }
            $objective = DB::table('cbt_questions')->where('cbt_bank_id', $bank)->where('type', 'objective')->orderBy('id')->pluck('id')->all();
            $theory = DB::table('cbt_questions')->where('cbt_bank_id', $bank)->where('type', 'theory')->orderBy('id')->pluck('id')->all();
            $this->put('exam_papers', [[
                'title' => $title, 'class_id' => $this->classes[$className] ?? null, 'subject_id' => $this->subjects[$subject] ?? null, 'term_id' => $this->termIdValue,
                'duration_minutes' => $minutes, 'instructions' => "Answer all questions in Section A and any two in Section B. Write your name and class clearly. No calculators unless the invigilator allows it.",
                'sections' => [
                    ['heading' => 'Section A: Objective', 'instructions' => 'Choose the correct option.', 'questions' => array_slice($objective, 0, 12)],
                    ['heading' => 'Section B: Theory', 'instructions' => 'Show all your working.', 'questions' => $theory],
                ],
                'show_marks' => true, 'created_by' => $admin->id,
            ]]);
        }
    }

    // ───────────────────────── report card comments ─────────────────────────

    private function reportCards(User $admin): void
    {
        foreach (\App\Http\Controllers\SchoolAdmin\CommentBankController::STARTERS as $permission => $rows) {
            foreach ($rows as [$min, $max, $text]) {
                $this->put('report_card_comment_bank', [['writer_permission' => $permission, 'min_average' => $min, 'max_average' => $max, 'comment' => $text]]);
            }
        }

        $signers = DB::table('report_card_signers')->where('school_id', $this->sid)->orderBy('sort_order')->get();
        $sheets = DB::table('result_sheets')->where('school_id', $this->sid)->whereIn('status', ['approved', 'published', 'locked'])->orderByDesc('term_id')->take(8)->get();
        foreach ($sheets as $sheet) {
            $averages = DB::table('term_result_summaries')->where('result_sheet_id', $sheet->id)->pluck('average', 'student_id');
            foreach ($signers as $signer) {
                if (! $signer->has_comment) {
                    continue;
                }
                $bank = DB::table('report_card_comment_bank')->where('school_id', $this->sid)->where('writer_permission', $signer->writer_permission)->get();
                foreach ($averages as $studentId => $avg) {
                    $match = $bank->first(fn ($c) => $avg >= $c->min_average && $avg <= $c->max_average);
                    if ($match) {
                        $this->put('report_card_remarks', [['result_sheet_id' => $sheet->id, 'student_id' => $studentId, 'report_card_signer_id' => $signer->id, 'comment' => str_replace(['{name}', '{he_she}', '{him_her}'], ['This pupil', 'he', 'him'], $match->comment), 'written_by' => $admin->id]]);
                    }
                }
            }
        }
    }

    // ───────────────────────── computer-based tests (use the real service) ─────────────────────────

    private function cbtTests(User $admin, $students): void
    {
        Auth::setUser($admin);
        $svc = app(CbtService::class);
        $plans = [
            ['Mathematics Class Test 1', 'JSS Mathematics', 'JSS 1', 'Mathematics', 'closed', 'CA1', true],
            ['English Language Class Test 1', 'English Language', 'JSS 1', 'English Language', 'closed', 'CA1', true],
            ['Intermediate Science Quiz', 'Intermediate Science', 'JSS 2', 'Intermediate Science', 'closed', 'CA1', false],
            ['Digital Technologies Test', 'Digital Technologies', 'JSS 2', 'Digital Technologies', 'published', null, false],
            ['Physics Mock Test', 'Physics', 'SS 3', 'Physics', 'published', null, true],
            ['Economics Weekly Test', 'Economics', 'SS 2', 'Economics', 'draft', null, false],
            ['Biology Class Test', 'Biology', 'SS 1', 'Biology', 'draft', null, false],
        ];
        foreach ($plans as [$title, $bankName, $className, $subject, $status, $part, $calc]) {
            $bank = DB::table('cbt_banks')->where('school_id', $this->sid)->where('name', $bankName)->first();
            $classId = $this->classes[$className] ?? null;
            if (! $bank || ! $classId) {
                continue;
            }
            $objective = DB::table('cbt_questions')->where('cbt_bank_id', $bank->id)->where('type', 'objective')->count();
            $theory = DB::table('cbt_questions')->where('cbt_bank_id', $bank->id)->where('type', 'theory')->count();
            $opens = $status === 'closed' ? Carbon::now()->subDays(mt_rand(10, 25)) : ($status === 'published' ? Carbon::now()->subHours(2) : Carbon::now()->addDays(5));
            $test = CbtTest::create([
                'school_id' => $this->sid, 'title' => $title, 'audience' => 'students', 'class_id' => $classId, 'subject_id' => $this->subjects[$subject] ?? null, 'term_id' => $this->termIdValue,
                'instructions' => 'Read each question carefully. Choose the best answer. You can change an answer before you hand in. Do not leave this page.',
                'duration_minutes' => 30, 'opens_at' => $opens, 'closes_at' => $status === 'published' ? Carbon::now()->addDays(2) : $opens->copy()->addDays(3),
                'shuffle_questions' => true, 'shuffle_options' => true, 'show_score' => true, 'allow_calculator' => $calc, 'access_code' => $status === 'published' ? '4821' : null,
                'max_page_leaves' => 5, 'pass_mark' => 50, 'status' => 'draft', 'created_by' => $admin->id,
            ]);
            DB::table('cbt_test_sections')->insert(array_filter([
                ['cbt_test_id' => $test->id, 'position' => 1, 'title' => 'Section A: Objective', 'cbt_bank_id' => $bank->id, 'topic' => null, 'difficulty' => null, 'question_type' => 'objective', 'question_count' => min(10, $objective)],
                $theory ? ['cbt_test_id' => $test->id, 'position' => 2, 'title' => 'Section B: Theory', 'cbt_bank_id' => $bank->id, 'topic' => null, 'difficulty' => null, 'question_type' => 'theory', 'question_count' => min(1, $theory)] : null,
            ]));

            if ($status === 'draft') {
                continue;
            }
            // Pupils sit it: mixed ability, so the scores spread out
            $sitters = $students->where('class_id', $classId)->shuffle()->take($status === 'closed' ? 18 : 6);
            $test->update(['status' => 'published']);
            foreach ($sitters as $i => $student) {
                $ability = mt_rand(35, 98) / 100;
                $paper = $svc->drawPaper($test->fresh('sections'));
                $answers = [];
                $written = [];
                $questions = DB::table('cbt_questions')->whereIn('id', array_column($paper, 'q'))->get()->keyBy('id');
                foreach ($paper as $line) {
                    $q = $questions[$line['q']];
                    if (($line['t'] ?? null) === 'theory') {
                        $written[$q->id] = 'In my answer I explained the idea step by step and gave an example from our class notes. '.$this->pick(['Therefore the answer is as shown.', 'This is what we learnt in class.', 'I used the formula and checked my working.']);
                    } elseif (mt_rand(0, 99) / 100 < $ability) {
                        $answers[$q->id] = array_search($q->correct_option, $line['o'], true);
                    } else {
                        $wrong = array_values(array_diff($line['o'], [$q->correct_option]));
                        $answers[$q->id] = array_search($this->pick($wrong), $line['o'], true);
                    }
                }
                $started = $status === 'published'
                    ? ($i >= 3 ? Carbon::now()->subMinutes(mt_rand(2, 18)) : Carbon::now()->subMinutes(mt_rand(40, 100)))
                    : $opens->copy()->addHours(mt_rand(1, 30))->addMinutes($i * 2);
                $attempt = CbtAttempt::create([
                    'school_id' => $this->sid, 'cbt_test_id' => $test->id, 'student_id' => $student->id, 'started_at' => $started, 'deadline_at' => $started->copy()->addMinutes(30),
                    'status' => 'in_progress', 'paper' => $paper, 'answers' => $answers, 'written' => $written, 'flags' => [], 'page_leaves' => mt_rand(0, 6) > 4 ? mt_rand(1, 3) : 0,
                ]);
                if ($status === 'published' && $i >= 3) {
                    continue; // still sitting
                }
                $svc->finish($attempt);
                $attempt->refresh();
                $attempt->update(['submitted_at' => $started->copy()->addMinutes(mt_rand(14, 29))]);
            }
            if ($status === 'closed') {
                // The teacher marks the theory question for most sittings
                $theoryQ = DB::table('cbt_questions')->where('cbt_bank_id', $bank->id)->where('type', 'theory')->first();
                if ($theoryQ) {
                    $marks = [];
                    foreach ($test->attempts()->where('needs_marking', true)->get() as $n => $a) {
                        if ($n % 5 === 4) {
                            continue; // leave a few waiting to be marked
                        }
                        $marks[$a->id] = ['marks' => round($theoryQ->marks * mt_rand(40, 100) / 100, 1), 'comment' => $this->pick([null, 'Good working.', 'Show more steps next time.', 'Well explained.'])];
                    }
                    $svc->markTheory($test, $theoryQ->id, $marks);
                }
                $test->update(['status' => 'closed']);
            }
        }
    }

    // ───────────────────────── ID cards and certificates ─────────────────────────

    private function idCardsAndCertificates(User $admin, $students): void
    {
        Auth::setUser($admin);
        $signer = DB::table('report_card_signers')->where('school_id', $this->sid)->orderBy('sort_order')->first();
        DB::table('id_card_designs')->where('school_id', $this->sid)->delete();
        DB::table('id_card_designs')->insert([
            ['school_id' => $this->sid, 'name' => 'Standard (portrait)', 'orientation' => 'portrait', 'template' => 'classic', 'primary_color' => null, 'accent_color' => null, 'title' => 'STUDENT ID CARD', 'show' => json_encode(\App\Models\IdCardDesign::SHOW), 'back_text' => 'If found, please return to Heritage Crown Academy, Lagos.', 'signer_id' => $signer?->id, 'valid_months' => 12, 'is_default' => true, 'created_at' => $this->now, 'updated_at' => $this->now],
        ]);

        $active = Student::where('school_id', $this->sid)->where('status', 'active')->orderBy('id')->take(120)->get();
        app(IdCardService::class)->issueMissing($active, $admin);

        $service = app(CertificateService::class);
        $chosen = $students->filter(fn ($s) => $s->class_id == ($this->classes['JSS 3'] ?? 0) || $s->class_id == ($this->classes['SS 3'] ?? 0))->take(4);
        foreach ($chosen as $i => $row) {
            $student = Student::find($row->id);
            $type = $i % 2 === 0 ? 'testimonial' : 'transfer';
            $details = $service->defaults($student, $type);
            $service->issue($student, $type, $details, $signer?->id, $admin, false);
        }
    }

    // ───────────────────────── fees paid in parts ─────────────────────────

    /** Put a payment plan on some open invoices, some already behind, so the "behind on parts" views have lines */
    private function instalments(User $admin): void
    {
        Auth::setUser($admin);
        $two = PaymentPlan::where('school_id', $this->sid)->where('name', 'Two instalments')->first();
        $three = PaymentPlan::where('school_id', $this->sid)->where('name', 'Three instalments')->first();
        if (! $two || ! $three) {
            return;
        }
        $open = Invoice::where('school_id', $this->sid)->whereIn('status', ['unpaid', 'partial'])->where('balance', '>', 0)->orderBy('id')->get();
        $svc = app(InstalmentService::class);
        $dates = fn (array $days) => collect($days)->map(fn ($d) => Carbon::now()->addDays($d)->toDateString())->all();
        foreach ($open->shuffle()->take(45) as $i => $invoice) {
            [$plan, $when] = $i % 3 === 0 ? [$three, [-30, 10, 40]] : ($i % 3 === 1 ? [$two, [-20, 25]] : [$two, [7, 45]]);
            $parts = [];
            foreach ($plan->parts as $k => $part) {
                $parts[] = ['label' => $part['label'], 'percent' => $part['percent'], 'due_date' => $dates($when)[$k]];
            }
            $svc->apply($invoice, $parts, $plan);
        }
    }
}
