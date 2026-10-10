<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** The end-of-year "move students up" screen, and undoing a move. */
class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotions) {}

    public function index(Request $request): Response
    {
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $year = $years->firstWhere('id', (int) $request->input('year_id'))
            ?? $years->firstWhere('is_current', true) ?? $years->first();
        $classes = SchoolClass::orderBy('numeric_name')->orderBy('id')->get(['id', 'school_id', 'name', 'numeric_name']);
        $sid = $request->user()->school_id ?? $classes->first()?->school_id;
        $passMark = min(100, max(0, (float) ($request->input('pass_mark') ?? ($sid ? SchoolSetting::get($sid, 'pass_mark', 40) : 40) ?? 40)));

        // How many students in each class are still waiting to be moved this year
        $waiting = $year
            ? $classes->mapWithKeys(fn (SchoolClass $c) => [$c->id => $this->promotions->waiting($c, $year)->count()])
            : collect();

        $class = $classes->firstWhere('id', (int) $request->input('class_id'));
        $next = $class ? $this->promotions->nextClass($class) : null;

        return Inertia::render('SchoolAdmin/Promotions/Index', [
            'years' => $years->map(fn ($y) => ['id' => $y->id, 'name' => $y->name, 'is_current' => $y->is_current]),
            'yearId' => $year?->id,
            'classes' => $classes->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'waiting' => $waiting[$c->id] ?? 0])->values(),
            'sections' => Section::orderBy('name')->get(['id', 'class_id', 'name']),
            'selected' => $class && $year ? [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'next_class_id' => $next?->id,
                'is_top' => $next === null,
                'pass_mark' => $passMark,
                'students' => $this->promotions->candidates($class, $year, $passMark),
            ] : null,
            'batches' => PromotionBatch::with(['academicYear:id,name', 'fromClass:id,name', 'toClass:id,name', 'performer:id,name', 'undoer:id,name'])
                ->latest('id')->limit(20)->get()
                ->map(fn (PromotionBatch $b) => [
                    'id' => $b->id,
                    'year' => $b->academicYear?->name,
                    'from' => $b->fromClass?->name,
                    'to' => $b->toClass?->name,
                    'promoted' => $b->promoted_count,
                    'repeated' => $b->repeated_count,
                    'graduated' => $b->graduated_count,
                    'by' => $b->performer?->name,
                    'at' => $b->created_at?->toDateString(),
                    'undone' => $b->undone_at ? ['by' => $b->undoer?->name, 'at' => $b->undone_at->toDateString()] : null,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // The class decides the school (Super Admin has none of their own); the scoped find keeps it to the user's school
        $from = SchoolClass::findOrFail((int) $request->input('from_class_id'));
        $sid = $from->school_id;

        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'to_class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'pass_mark' => 'nullable|numeric|min:0|max:100',
            'decisions' => 'required|array|min:1|max:1000',
            'decisions.*.student_id' => 'required|integer|distinct',
            'decisions.*.outcome' => 'required|string|in:promoted,repeated,graduated',
            'decisions.*.section_id' => 'nullable|integer',
            'decisions.*.reason' => 'nullable|string|max:500',
        ]);

        $year = AcademicYear::findOrFail($data['academic_year_id']);
        $to = isset($data['to_class_id']) ? SchoolClass::findOrFail($data['to_class_id']) : null;

        $batch = $this->promotions->run($from, $year, $to, $data['pass_mark'] ?? null, $data['decisions'], $request->user());

        $parts = array_filter([
            $batch->promoted_count ? "{$batch->promoted_count} moved up to ".($to?->name ?? '') : null,
            $batch->repeated_count ? "{$batch->repeated_count} repeating {$from->name}" : null,
            $batch->graduated_count ? "{$batch->graduated_count} graduated" : null,
        ]);

        return redirect()->route('school.promotions.index', ['year_id' => $year->id])
            ->with('success', 'Done: '.implode(', ', $parts).'.');
    }

    public function undo(Request $request, PromotionBatch $promotionBatch): RedirectResponse
    {
        $this->promotions->undo($promotionBatch, $request->user());

        return back()->with('success', 'Undone. The students are back in '.($promotionBatch->fromClass?->name ?? 'their old class').'.');
    }

    /** Used by the student profile: where the student has been, year by year */
    public static function historyFor(Student $student): array
    {
        return $student->promotions()
            ->whereHas('batch', fn ($q) => $q->whereNull('undone_at'))
            ->with(['batch.academicYear:id,name', 'batch.performer:id,name', 'fromClass:id,name', 'toClass:id,name', 'toSection:id,name'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'year' => $p->batch?->academicYear?->name,
                'outcome' => $p->outcome,
                'from' => $p->fromClass?->name,
                'to' => $p->toClass?->name,
                'to_section' => $p->toSection?->name,
                'year_average' => $p->year_average,
                'reason' => $p->reason,
                'by' => $p->batch?->performer?->name,
                'at' => $p->created_at?->toDateString(),
            ])->all();
    }
}
