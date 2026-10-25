<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Invoice;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectScore;
use App\Models\Term;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A read-only look at every class and every teacher for one term, for the principal. The question it
 * answers best: "who has not finished entering scores?"
 */
class OverviewController extends Controller
{
    public function index(Request $request): Response
    {
        $sid = $this->getSchoolId();

        $year  = AcademicYear::where('school_id', $sid)->where('is_current', true)->first();
        $terms = Term::where('school_id', $sid)->when($year, fn ($q) => $q->where('academic_year_id', $year->id))->orderBy('sequence')->get(['id', 'name', 'is_current']);
        $term  = $terms->firstWhere('id', $request->integer('term_id')) ?? $terms->firstWhere('is_current', true) ?? $terms->first();

        $students = Student::where('status', 'active')->with('section:id,department')->get(['id', 'class_id', 'section_id']);
        $classOf  = $students->pluck('class_id', 'id');
        $sheets   = $term ? ResultSheet::where('term_id', $term->id)->get()->keyBy('class_id') : collect();

        // Students with at least one score for a subject, per sheet
        $scored = $sheets->isEmpty() ? collect() : SubjectScore::whereIn('result_sheet_id', $sheets->pluck('id'))
            ->selectRaw('result_sheet_id, subject_id, count(distinct student_id) as n')->groupBy('result_sheet_id', 'subject_id')->get()
            ->mapWithKeys(fn ($r) => [$r->result_sheet_id.'-'.$r->subject_id => (int) $r->n]);

        // One line per subject a class offers: how many of the students who take it have scores so far
        $lines = ClassSubject::with(['subject:id,name', 'teacher:id,first_name,last_name'])->get()->map(function (ClassSubject $l) use ($students, $sheets, $scored) {
            $expected = $students->where('class_id', $l->class_id)->filter(fn ($s) => $l->takenBy($s->section?->department))->count();
            $sheet    = $sheets->get($l->class_id);
            $done     = $sheet ? ($scored[$sheet->id.'-'.$l->subject_id] ?? 0) : 0;

            return [
                'class_id' => $l->class_id, 'subject' => $l->subject?->name ?? 'Subject', 'teacher_id' => $l->teacher_id,
                'teacher' => $l->teacher?->full_name, 'scored' => $done, 'expected' => $expected,
                'finished' => $expected === 0 || $done >= $expected,
            ];
        })->filter(fn ($l) => $l['expected'] > 0)->values();

        // Attendance over the last 7 days and fees still owed, per class
        $attendance = Attendance::where('attendable_type', Student::class)->whereDate('date', '>=', now()->subDays(6)->toDateString())
            ->get(['attendable_id', 'status'])->groupBy(fn ($a) => $classOf[$a->attendable_id] ?? 0);
        $owed = Invoice::whereIn('status', ['unpaid', 'partial'])->selectRaw('student_id, sum(balance) as owed')->groupBy('student_id')->pluck('owed', 'student_id');

        $classes = SchoolClass::orderBy('numeric_name')->orderBy('name')->get()->map(function (SchoolClass $c) use ($students, $sheets, $lines, $attendance, $owed, $classOf) {
            $mine  = $lines->where('class_id', $c->id);
            $att   = $attendance->get($c->id);
            $sheet = $sheets->get($c->id);

            return [
                'id' => $c->id, 'name' => $c->name,
                'students' => $students->where('class_id', $c->id)->count(),
                'attendance' => $att && $att->count() ? round($att->where('status', 'present')->count() / $att->count() * 100) : null,
                'fees_owed' => (float) $classOf->filter(fn ($cid) => $cid === $c->id)->keys()->sum(fn ($id) => (float) ($owed[$id] ?? 0)),
                'sheet_status' => $sheet?->status, 'average' => $sheet?->class_average !== null ? round((float) $sheet->class_average, 1) : null,
                'subjects' => $mine->count(), 'subjects_done' => $mine->where('finished', true)->count(),
                'unfinished' => $mine->where('finished', false)->values(),
            ];
        })->filter(fn ($c) => $c['students'] > 0 || $c['subjects'] > 0)->values();

        $classNames = $classes->pluck('name', 'id');
        $teachers = $lines->groupBy(fn ($l) => $l['teacher_id'] ?? 0)->map(fn ($group) => [
            'name' => $group->first()['teacher'] ?? 'No teacher chosen yet',
            'total' => $group->count(), 'finished' => $group->where('finished', true)->count(),
            'lines' => $group->map(fn ($l) => [
                'class' => $classNames[$l['class_id']] ?? '', 'subject' => $l['subject'], 'scored' => $l['scored'], 'expected' => $l['expected'], 'finished' => $l['finished'],
            ])->sortBy('class')->values(),
        ])->sortBy([fn ($a, $b) => ($a['finished'] === $a['total']) <=> ($b['finished'] === $b['total']), fn ($a, $b) => $a['name'] <=> $b['name']])->values();

        return Inertia::render('SchoolAdmin/Overview/Index', [
            'terms' => $terms, 'termId' => $term?->id, 'classes' => $classes, 'teachers' => $teachers,
        ]);
    }
}
