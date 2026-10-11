<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\ExamPaper;
use App\Models\ReportCardDesign;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ReportCardService;
use App\Support\EmbeddedImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Printed exam papers: questions picked from the question banks into sections, printed with the school's heading, and a marking scheme */
class ExamPaperController extends Controller
{
    public function index(Request $request): Response
    {
        $sid = $this->getSchoolId();
        $papers = ExamPaper::with(['schoolClass:id,name', 'subject:id,name', 'term:id,name', 'creator:id,name'])->where('school_id', $sid)
            ->when($request->class_id, fn ($q, $c) => $q->where('class_id', $c))
            ->when($request->term_id, fn ($q, $t) => $q->where('term_id', $t))
            ->latest('updated_at')->get();
        $marks = CbtQuestion::withTrashed()->where('school_id', $sid)->whereIn('id', $papers->flatMap->questionIds()->unique())->pluck('marks', 'id');

        return Inertia::render('SchoolAdmin/Cbt/Papers', [
            'papers' => $papers->map(fn (ExamPaper $p) => [
                'id' => $p->id, 'title' => $p->title, 'class' => $p->schoolClass?->name, 'subject' => $p->subject?->name, 'term' => $p->term?->name,
                'questions' => count($p->questionIds()),
                'marks' => (float) collect($p->questionIds())->sum(fn ($id) => (float) ($marks[$id] ?? 0)),
                'by' => $p->creator?->name, 'updated' => $p->updated_at?->toDateString(),
            ]),
            'filters' => ['class_id' => $request->class_id, 'term_id' => $request->term_id],
            'canManage' => $request->user()->can('cbt.manage'),
        ] + $this->choices($sid));
    }

    public function create(Request $request): Response
    {
        $sid = $this->getSchoolId();
        $term = Term::where('school_id', $sid)->where('is_current', true)->first();

        return $this->editor($request, null, [
            'title' => $term ? $term->name.' Examination' : 'Examination',
            'class_id' => $request->integer('class_id') ?: null, 'subject_id' => $request->integer('subject_id') ?: null, 'term_id' => $term?->id,
            'duration_minutes' => 120, 'instructions' => '', 'show_marks' => true,
            'sections' => [
                ['title' => 'Section A: Objectives', 'instructions' => 'Answer all the questions. Choose the correct option from the letters A to D.', 'questions' => []],
                ['title' => 'Section B: Theory', 'instructions' => 'Answer any four questions.', 'questions' => []],
            ],
        ]);
    }

    public function show(Request $request, ExamPaper $paper): Response
    {
        return $this->editor($request, $paper, [
            'title' => $paper->title, 'class_id' => $paper->class_id, 'subject_id' => $paper->subject_id, 'term_id' => $paper->term_id,
            'duration_minutes' => $paper->duration_minutes, 'instructions' => $paper->instructions ?? '', 'show_marks' => $paper->show_marks,
            'sections' => $paper->sections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $paper = ExamPaper::create($this->validated($request) + ['school_id' => $this->getSchoolId(), 'created_by' => $request->user()->id]);

        return redirect("/school/cbt/papers/{$paper->id}")->with('success', 'Exam paper saved. Print it, or print the marking scheme.');
    }

    public function update(Request $request, ExamPaper $paper): RedirectResponse
    {
        $paper->update($this->validated($request));

        return back()->with('success', 'Exam paper saved.');
    }

    public function destroy(ExamPaper $paper): RedirectResponse
    {
        $paper->delete();

        return redirect('/school/cbt/papers')->with('success', 'Exam paper deleted. The questions stay in their banks.');
    }

    /** Questions from one bank for the "Add questions" picker */
    public function bankQuestions(Request $request): JsonResponse
    {
        $request->validate([
            'bank' => ['required', Rule::exists('cbt_banks', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'type' => ['nullable', Rule::in(CbtQuestion::TYPES)],
        ]);
        $questions = CbtQuestion::where('cbt_bank_id', $request->bank)
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('topic')->orderBy('id')->limit(1000)->get();

        return response()->json(['questions' => $questions->map(fn (CbtQuestion $q) => CbtBankController::questionRow($q))]);
    }

    /** The question paper, ready to print and hand out */
    public function pdf(ExamPaper $paper, ReportCardService $cards): \Illuminate\Http\Response
    {
        return $this->render($paper, $cards, false)->stream($this->fileName($paper, 'paper'));
    }

    /** The answers: the objective key and each theory question's marking guide */
    public function markingScheme(ExamPaper $paper, ReportCardService $cards): \Illuminate\Http\Response
    {
        return $this->render($paper, $cards, true)->stream($this->fileName($paper, 'marking-scheme'));
    }

    private function render(ExamPaper $paper, ReportCardService $cards, bool $answers): \Barryvdh\DomPDF\PDF
    {
        $paper->loadMissing(['schoolClass:id,name', 'subject:id,name', 'term.academicYear:id,name']);
        $questions = $paper->questions();
        $sections = $paper->filled($questions);
        $design = $cards->design(ReportCardDesign::forClass($paper->school_id, $paper->class_id));

        return Pdf::loadView('exam-papers.paper', [
            'paper' => $paper,
            'sections' => $sections,
            'images' => $answers ? [] : $questions->filter(fn ($q) => $q->image_path)->map(fn ($q) => EmbeddedImage::from($q->image_path))->filter()->all(),
            'total' => (float) collect($sections)->flatMap(fn ($s) => $s['questions'])->sum('marks'),
            'school' => $cards->school($paper->school_id),
            'color' => $design['primary'],
            'answers' => $answers,
        ])->setPaper('a4');
    }

    private function fileName(ExamPaper $paper, string $kind): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '-', implode(' ', array_filter([$paper->subject?->name, $paper->schoolClass?->name, $kind]))), '-');

        return strtolower($name ?: $kind).'.pdf';
    }

    private function editor(Request $request, ?ExamPaper $paper, array $data): Response
    {
        $sid = $this->getSchoolId();
        $ids = collect($data['sections'])->flatMap(fn ($s) => $s['questions'] ?? [])->unique();
        $questions = CbtQuestion::withTrashed()->where('school_id', $sid)->whereIn('id', $ids)->get();

        return Inertia::render('SchoolAdmin/Cbt/Paper', [
            'paper' => $paper ? ['id' => $paper->id] + $data : null,
            'initial' => $data,
            'questions' => $questions->mapWithKeys(fn (CbtQuestion $q) => [$q->id => CbtBankController::questionRow($q) + ['deleted' => $q->trashed()]]),
            'banks' => CbtBank::with('subject.schoolClass:id,name')->where('school_id', $sid)->withCount('questions')->orderBy('name')->get()
                ->map(fn (CbtBank $b) => ['id' => $b->id, 'name' => $b->name, 'subject_id' => $b->subject_id, 'questions' => $b->questions_count]),
            'canManage' => $request->user()->can('cbt.manage'),
            'limits' => ['sections' => ExamPaper::MAX_SECTIONS, 'questions' => ExamPaper::MAX_QUESTIONS],
        ] + $this->choices($sid));
    }

    private function choices(int $sid): array
    {
        return [
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $sid)->orderBy('name')->get(['id', 'name', 'class_id']),
            'terms' => Term::with('academicYear:id,name')->where('school_id', $sid)->orderByDesc('start_date')->get(['id', 'name', 'academic_year_id', 'is_current'])
                ->map(fn ($t) => ['id' => $t->id, 'name' => trim($t->name.' '.($t->academicYear?->name ?? '')), 'is_current' => (bool) $t->is_current]),
        ];
    }

    private function validated(Request $request): array
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'class_id' => ['nullable', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $sid)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)],
            'duration_minutes' => 'nullable|integer|min:5|max:600',
            'instructions' => 'nullable|string|max:2000',
            'show_marks' => 'boolean',
            'sections' => 'required|array|min:1|max:'.ExamPaper::MAX_SECTIONS,
            'sections.*.title' => 'required|string|max:120',
            'sections.*.instructions' => 'nullable|string|max:1000',
            'sections.*.questions' => 'present|array',
            'sections.*.questions.*' => 'integer',
        ], ['sections.*.title.required' => 'Give every section a heading.']);

        $ids = collect($data['sections'])->flatMap(fn ($s) => $s['questions'])->map(fn ($id) => (int) $id);
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages(['sections' => 'Add at least one question to the paper.']);
        }
        if ($ids->count() > ExamPaper::MAX_QUESTIONS) {
            throw ValidationException::withMessages(['sections' => 'A paper can hold at most '.ExamPaper::MAX_QUESTIONS.' questions.']);
        }
        if ($ids->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['sections' => 'A question is on the paper twice. Remove one of them.']);
        }
        // Only this school's questions; ones already on the paper may since have been deleted from their bank
        $known = CbtQuestion::withTrashed()->where('school_id', $sid)->whereIn('id', $ids)->pluck('id');
        if ($known->count() !== $ids->count()) {
            throw ValidationException::withMessages(['sections' => 'Some questions could not be found. Reload the page and try again.']);
        }

        $data['sections'] = collect($data['sections'])->map(fn ($s) => [
            'title' => $s['title'], 'instructions' => ($s['instructions'] ?? null) ?: null, 'questions' => array_map('intval', $s['questions']),
        ])->all();
        $data['instructions'] = ($data['instructions'] ?? null) ?: null;
        $data['show_marks'] = $request->boolean('show_marks');

        return $data;
    }
}
