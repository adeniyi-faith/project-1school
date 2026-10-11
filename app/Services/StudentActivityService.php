<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BookIssue;
use App\Models\CbtAttempt;
use App\Models\HostelAllocation;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\TermResultSummary;
use App\Models\TransportRoute;
use App\Models\User;

/**
 * What a student has been doing around the school, for their profile: boarding, library, bus, fees, attendance,
 * term results and computer tests. Each part is built only when the viewer holds the matching permission, and is
 * null otherwise, so the profile never shows (or leaks) a part the person could not open on its own.
 */
class StudentActivityService
{
    public function for(Student $student, User $user): array
    {
        return [
            'hostel' => $user->can('hostel.view') ? $this->hostel($student) : null,
            'library' => $user->can('library.view') ? $this->library($student) : null,
            'transport' => $user->can('transport.view') ? $this->transport($student) : null,
            'fees' => $user->can('fees.view') ? $this->fees($student) : null,
            'attendance' => $user->can('attendance.view') ? $this->attendance($student) : null,
            'results' => $user->can('results.view') ? $this->results($student) : null,
            'tests' => $user->can('cbt.view') ? $this->tests($student) : null,
        ];
    }

    private function hostel(Student $s): array
    {
        return HostelAllocation::with('hostel:id,name', 'room:id,room_no,floor')->where('student_id', $s->id)->orderByRaw("status = 'active' desc")->latest('joining_date')->get()
            ->map(fn (HostelAllocation $a) => [
                'id' => $a->id, 'hostel' => $a->hostel?->name, 'room' => $a->room?->room_no, 'floor' => $a->room?->floor, 'bed' => $a->bed_no,
                'joined' => $a->joining_date?->toDateString(), 'left' => $a->leaving_date?->toDateString(), 'status' => $a->status,
            ])->values()->all();
    }

    private function library(Student $s): array
    {
        return BookIssue::with('book:id,title,author')->where('member_type', Student::class)->where('member_id', $s->id)->latest('issued_date')->limit(50)->get()
            ->map(fn (BookIssue $i) => [
                'id' => $i->id, 'title' => $i->book?->title, 'author' => $i->book?->author, 'issued' => $i->issued_date?->toDateString(), 'due' => $i->due_date?->toDateString(),
                'returned' => $i->returned_date?->toDateString(), 'fine' => (float) $i->fine, 'status' => $i->status,
            ])->values()->all();
    }

    private function transport(Student $s): array
    {
        return TransportRoute::with('vehicle:id,name,registration_no')->whereHas('students', fn ($q) => $q->where('students.id', $s->id))->get()
            ->map(fn (TransportRoute $r) => [
                'id' => $r->id, 'route' => $r->name, 'from' => $r->start_point, 'to' => $r->end_point, 'vehicle' => $r->vehicle?->name,
                'plate' => $r->vehicle?->registration_no, 'stop' => $r->students()->where('students.id', $s->id)->first()?->pivot->stop,
            ])->values()->all();
    }

    private function fees(Student $s): array
    {
        $invoices = Invoice::with('feeStructure.feeCategory:id,name', 'term:id,name')->where('student_id', $s->id)->where('status', '!=', 'cancelled')->latest('id')->limit(50)->get();

        return [
            'owed' => (float) $invoices->sum('balance'),
            'invoices' => $invoices->map(fn (Invoice $i) => [
                'id' => $i->id, 'no' => $i->invoice_no, 'fee' => $i->feeStructure?->feeCategory?->name, 'term' => $i->term?->name, 'amount' => (float) $i->amount,
                'balance' => (float) $i->balance, 'status' => $i->status, 'due' => $i->due_date?->toDateString(),
            ])->values()->all(),
        ];
    }

    private function attendance(Student $s): array
    {
        $counts = Attendance::where('attendable_type', Student::class)->where('attendable_id', $s->id)->where('date', '>=', now()->subDays(90)->toDateString())
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $days = (int) $counts->sum();

        return [
            'days' => $days, 'present' => (int) ($counts['present'] ?? 0), 'late' => (int) ($counts['late'] ?? 0), 'absent' => (int) ($counts['absent'] ?? 0),
            'half_day' => (int) ($counts['half_day'] ?? 0),
            'rate' => $days ? round((($counts['present'] ?? 0) + ($counts['late'] ?? 0)) / $days * 100) : null,
        ];
    }

    private function results(Student $s): array
    {
        return TermResultSummary::with('sheet.term:id,name', 'sheet:id,term_id,class_id,status')->where('student_id', $s->id)
            ->whereHas('sheet', fn ($q) => $q->whereIn('status', ['published', 'locked']))->latest('id')->limit(12)->get()
            ->map(fn (TermResultSummary $r) => [
                'id' => $r->id, 'sheet_id' => $r->result_sheet_id, 'term' => $r->sheet?->term?->name, 'average' => $r->average, 'position' => $r->position, 'class_size' => $r->class_size,
            ])->values()->all();
    }

    private function tests(Student $s): array
    {
        return CbtAttempt::with('test:id,title')->where('student_id', $s->id)->where('status', '!=', 'in_progress')->latest('id')->limit(30)->get()
            ->map(fn (CbtAttempt $a) => [
                'id' => $a->id, 'test_id' => $a->cbt_test_id, 'title' => $a->test?->title, 'when' => $a->submitted_at?->toDateString(),
                'percent' => $a->max_score && ! $a->needs_marking ? round($a->score / $a->max_score * 100, 1) : null, 'needs_marking' => (bool) $a->needs_marking,
            ])->values()->all();
    }
}
