<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Mark;
use App\Models\Student;
use App\Services\FeeLedgerService;
use App\Services\TermResultService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ParentPortalController extends Controller
{
    public function dashboard()
    {
        $user     = auth()->user();
        $guardian = Guardian::with('students.schoolClass:id,name', 'students.section:id,name')
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $guardian) {
            return Inertia::render('Parent/Dashboard', [
                'guardian'  => null,
                'linked'    => false,
                'children'  => [],
                'announcements' => [],
            ]);
        }

        $now   = Carbon::now();
        $today = $now->toDateString();

        $children = $guardian->students->map(function (Student $student) use ($now, $today) {

            /* Attendance this month */
            $attRows = Attendance::where('school_id', $student->school_id)
                ->where('attendable_type', Student::class)
                ->where('attendable_id', $student->id)
                ->whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->select('status')
                ->get();

            $total   = $attRows->count();
            $present = $attRows->where('status', 'present')->count();

            /* Fee summary (from invoices) */
            $account = app(FeeLedgerService::class)->familyAccount($student);

            /* Recent marks */
            $marks = Mark::where('school_id', $student->school_id)
                ->where('student_id', $student->id)
                ->visibleToFamilies()
                ->with(['exam:id,name', 'subject:id,name,full_marks,pass_marks'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($m) => [
                    'exam'    => $m->exam?->name,
                    'subject' => $m->subject?->name,
                    'marks'   => $m->marks_obtained,
                    'grade'   => $m->grade,
                    'absent'  => $m->is_absent,
                ]);

            $recentFees = $account['rows']->take(3)->map(fn ($r) => [
                'month' => $r['month'], 'paid' => $r['paid'], 'balance' => $r['balance'], 'status' => $r['status'],
            ])->values();

            return [
                'id'           => $student->id,
                'full_name'    => $student->full_name,
                'admission_no' => $student->admission_no,
                'class'        => $student->schoolClass?->name,
                'section'      => $student->section?->name,
                'photo_url'    => $student->photo_url,
                'attendance'   => [
                    'total'      => $total,
                    'present'    => $present,
                    'absent'     => $attRows->where('status', 'absent')->count(),
                    'percentage' => $total ? round(($present / $total) * 100) : 0,
                ],
                'fees' => [
                    'total_due'  => $account['total_due'],
                    'total_paid' => $account['total_paid'],
                    'balance'    => $account['balance'],
                    'recent'     => $recentFees,
                ],
                'marks'      => $marks,
            ];
        });

        /* Announcements for parents */
        $announcements = Announcement::where('school_id', $user->school_id)
            ->where(fn ($q) => $q->where('audience', 'all')->orWhere('audience', 'parents'))
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id'     => $a->id,
                'title'  => $a->title,
                'body'   => $a->body,
                'pinned' => $a->is_pinned,
                'date'   => $a->published_at ? Carbon::parse($a->published_at)->diffForHumans() : null,
            ]);

        return Inertia::render('Parent/Dashboard', [
            'linked'       => true,
            'guardian'     => [
                'id'    => $guardian->id,
                'name'  => $guardian->name,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
            ],
            'children'     => $children,
            'announcements'=> $announcements,
        ]);
    }

    private function resolveGuardian()
    {
        $user = auth()->user();
        return Guardian::with('students.schoolClass:id,name', 'students.section:id,name')
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->first();
    }

    private function notLinked(string $page)
    {
        return Inertia::render($page, ['linked' => false, 'guardian' => null, 'children' => []]);
    }

    public function attendance()
    {
        $guardian = $this->resolveGuardian();
        if (! $guardian) return $this->notLinked('Parent/Attendance');

        $now      = Carbon::now();
        $children = $guardian->students->map(function (Student $student) use ($now) {
            $months = [];
            for ($m = 0; $m < 3; $m++) {
                $month = $now->copy()->subMonths($m);
                $rows  = Attendance::where('school_id', $student->school_id)
                    ->where('attendable_type', Student::class)
                    ->where('attendable_id', $student->id)
                    ->whereMonth('date', $month->month)
                    ->whereYear('date', $month->year)
                    ->orderBy('date')
                    ->get(['date', 'status']);

                $total   = $rows->count();
                $present = $rows->where('status', 'present')->count();
                $months[] = [
                    'label'      => $month->format('M Y'),
                    'total'      => $total,
                    'present'    => $present,
                    'absent'     => $rows->where('status', 'absent')->count(),
                    'late'       => $rows->where('status', 'late')->count(),
                    'percentage' => $total ? round($present / $total * 100) : 0,
                    'calendar'   => $rows->map(fn ($r) => ['date' => $r->date, 'status' => $r->status]),
                ];
            }
            return [
                'id'        => $student->id,
                'full_name' => $student->full_name,
                'class'     => $student->schoolClass?->name,
                'section'   => $student->section?->name,
                'months'    => $months,
            ];
        });

        return Inertia::render('Parent/Attendance', [
            'linked'   => true,
            'guardian' => ['name' => $guardian->name],
            'children' => $children,
        ]);
    }

    public function results()
    {
        $guardian = $this->resolveGuardian();
        if (! $guardian) return $this->notLinked('Parent/Results');

        $children = $guardian->students->map(function (Student $student) {
            $marks = \App\Models\Mark::where('school_id', $student->school_id)
                ->where('student_id', $student->id)
                ->visibleToFamilies()
                ->with(['exam:id,name,type', 'subject:id,name'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($m) => [
                    'exam'    => $m->exam?->name,
                    'type'    => $m->exam?->type,
                    'subject' => $m->subject?->name,
                    'marks'   => $m->marks_obtained,
                    'total'   => $m->total_marks,
                    'grade'   => $m->grade,
                    'absent'  => $m->is_absent,
                ]);
            return [
                'id'        => $student->id,
                'full_name' => $student->full_name,
                'class'     => $student->schoolClass?->name,
                'marks'     => $marks,
                'reports'   => app(TermResultService::class)->familyReports($student),
            ];
        });

        return Inertia::render('Parent/Results', [
            'linked'   => true,
            'guardian' => ['name' => $guardian->name],
            'children' => $children,
        ]);
    }

    public function fees()
    {
        $guardian = $this->resolveGuardian();
        if (! $guardian) return $this->notLinked('Parent/Fees');

        $children = $guardian->students->map(function (Student $student) {
            $account = app(FeeLedgerService::class)->familyAccount($student);

            return [
                'id'         => $student->id,
                'full_name'  => $student->full_name,
                'class'      => $student->schoolClass?->name,
                'total_due'  => $account['total_due'],
                'total_paid' => $account['total_paid'],
                'balance'    => $account['balance'],
                'payments'   => $account['rows'],
            ];
        });

        return Inertia::render('Parent/Fees', [
            'linked'   => true,
            'guardian' => ['name' => $guardian->name],
            'children' => $children,
        ]);
    }

    public function announcements()
    {
        $user     = auth()->user();
        $guardian = Guardian::where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        if (! $guardian) return $this->notLinked('Parent/Announcements');

        $announcements = Announcement::where('school_id', $user->school_id)
            ->where(fn ($q) => $q->where('audience', 'all')->orWhere('audience', 'parents'))
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'body', 'is_pinned', 'published_at'])
            ->map(fn ($a) => [
                'id'     => $a->id,
                'title'  => $a->title,
                'body'   => $a->body,
                'pinned' => $a->is_pinned,
                'date'   => $a->published_at ? Carbon::parse($a->published_at)->format('d M Y') : null,
            ]);

        return Inertia::render('Parent/Announcements', [
            'linked'        => true,
            'guardian'      => ['name' => $guardian->name],
            'announcements' => $announcements,
        ]);
    }
}
