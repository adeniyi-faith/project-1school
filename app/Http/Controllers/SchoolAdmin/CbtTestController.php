<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\CbtTest;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Services\CbtService;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Setting up tests, opening and closing them, and their results */
class CbtTestController extends Controller
{
    public function __construct(private CbtService $cbt) {}

    public function index(Request $request): Response
    {
        $tests = CbtTest::with('schoolClass:id,name', 'subject:id,name', 'sections')->withCount([
            'attempts', 'attempts as finished_count' => fn ($q) => $q->where('status', '!=', 'in_progress'),
        ])->latest('opens_at')->paginate(20)->withQueryString();

        $tests->through(fn (CbtTest $t) => $this->row($t) + ['attempts' => $t->attempts_count, 'finished' => $t->finished_count]);

        return Inertia::render('SchoolAdmin/Cbt/Tests', [
            'tests' => $tests,
            'canManage' => $request->user()->can('cbt.manage'),
            'banks' => CbtBank::count(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(CbtTest $test): Response
    {
        return $this->form($test->load('sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $test = DB::transaction(function () use ($data, $request) {
            $test = CbtTest::create($data['test'] + ['school_id' => $request->user()->school_id, 'created_by' => $request->user()->id, 'status' => 'draft']);
            $this->saveSections($test, $data['sections']);

            return $test;
        });

        return redirect("/school/cbt/tests/{$test->id}")->with('success', 'Test saved as a draft. Open it to students when you are ready.');
    }

    public function update(Request $request, CbtTest $test): RedirectResponse
    {
        $data = $this->validated($request);
        $started = $test->attempts()->exists();
        DB::transaction(function () use ($data, $test, $started) {
            $test->update($data['test']);
            if (! $started) {
                $this->saveSections($test, $data['sections']);
            }
        });

        return redirect("/school/cbt/tests/{$test->id}")->with('success', $started
            ? 'Test saved. People have already started it, so its questions were not changed.'
            : 'Test saved.');
    }

    public function destroy(CbtTest $test): RedirectResponse
    {
        if ($test->attempts()->exists()) {
            return back()->with('error', 'People have sat this test, so it cannot be deleted. Close it instead.');
        }
        $test->delete();

        return redirect('/school/cbt/tests')->with('success', 'Test deleted.');
    }

    /** Open to students, or close */
    public function status(Request $request, CbtTest $test): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(CbtTest::STATUSES)]])['status'];
        if ($status === 'published' && $problems = $this->cbt->problems($test->load('sections'))) {
            return back()->withErrors(CbtBankController::numbered('status', $problems))->with('error', 'The test cannot be opened yet.');
        }
        if ($status === 'draft' && $test->attempts()->exists()) {
            return back()->with('error', 'People have started this test, so it cannot go back to draft. Close it instead.');
        }
        if ($status === 'closed') {
            $this->cbt->settleTest($test);
        }
        $test->update(['status' => $status]);

        return back()->with('success', match ($status) {
            'published' => 'The test is open. Students in the class can start it in their portal from its opening time.',
            'closed' => 'The test is closed. Nobody can start it now; anyone still writing can finish their time.',
            default => 'The test is back to draft.',
        });
    }

    public function show(Request $request, CbtTest $test): Response
    {
        $test->load('sections.bank:id,name', 'schoolClass:id,name', 'subject:id,name', 'term:id,name');
        $results = $this->cbt->results($test);
        $finished = $results->whereIn('status', ['submitted', 'timed_out']);
        $scheme = $test->subject ? AssessmentScheme::forSubject($test->subject) : null;

        return Inertia::render('SchoolAdmin/Cbt/Test', [
            'test' => $this->row($test) + [
                'instructions' => $test->instructions,
                'access_code' => $request->user()->can('cbt.manage') ? $test->access_code : null,
                'sections' => $test->sections->map(fn ($s) => ['title' => $s->title, 'bank' => $s->bank?->name, 'topic' => $s->topic, 'difficulty' => $s->difficulty, 'count' => $s->question_count]),
            ],
            'problems' => $test->status === 'draft' ? $this->cbt->problems($test) : [],
            'results' => $results->values(),
            'summary' => [
                'expected' => $results->count(),
                'finished' => $finished->count(),
                'writing' => $results->where('status', 'in_progress')->count(),
                'average' => $finished->count() ? round($finished->avg('percent'), 1) : null,
                'highest' => $finished->max('percent'),
                'lowest' => $finished->min('percent'),
                'passed' => $test->pass_mark !== null ? $finished->where('percent', '>=', $test->pass_mark)->count() : null,
            ],
            'questionReport' => $this->cbt->questionReport($test),
            'scoreParts' => $scheme ? $scheme->components->map(fn ($c) => ['id' => $c->id, 'label' => "{$c->name} (out of {$c->max_score})"])->values() : [],
            'canManage' => $request->user()->can('cbt.manage'),
            'canCopy' => $request->user()->can('marks.entry'),
        ]);
    }

    public function export(CbtTest $test): HttpResponse
    {
        $test->load('sections');
        $results = $this->cbt->results($test);
        $attempts = $test->attempts()->get()->keyBy('student_id');
        $titles = $test->sections->pluck('title')->all();
        $rows = [array_merge(['Admission no.', 'Name', 'Status', 'Score', 'Out of', 'Percent'], $titles, ['Answered', 'Left the page', 'Minutes'])];
        foreach ($results as $r) {
            $a = $attempts->get($r['student_id']);
            $sections = array_map(fn ($i) => isset($a?->section_scores[$i]) ? $a->section_scores[$i]['score'].' / '.$a->section_scores[$i]['max'] : '', array_keys($titles));
            $rows[] = array_merge([$r['admission_no'], $r['name'], self::STATUS_WORDS[$r['status']], $r['score'], $r['max_score'], $r['percent']], $sections,
                [$r['answered'].' of '.$r['questions'], $a?->page_leaves ?? '', $r['minutes']]);
        }
        $bytes = (new SimpleXlsx())->sheet('Results', $rows)->bytes();

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($test->title).'-results.xlsx"',
        ]);
    }

    /** Extra minutes for one person, for example after a power cut */
    public function addTime(Request $request, CbtTest $test, CbtAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->cbt_test_id === $test->id, 404);
        $minutes = (int) $request->validate(['minutes' => 'required|integer|min:1|max:180'])['minutes'];
        $this->cbt->addTime($attempt, $minutes);

        return back()->with('success', "{$minutes} more minute(s) given. They can carry on from where they stopped.");
    }

    /** Remove a sitting so the person can start again with a new paper */
    public function reset(CbtTest $test, CbtAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->cbt_test_id === $test->id, 404);
        $attempt->delete();

        return back()->with('success', 'Their sitting was removed. They can start the test again with a new set of questions.');
    }

    public function copyToResults(Request $request, CbtTest $test): RedirectResponse
    {
        $id = (int) $request->validate(['component_id' => 'required|integer'])['component_id'];
        $count = $this->cbt->copyToResults($test->load('subject'), $id);

        return back()->with('success', "{$count} score(s) were put into the term results.");
    }

    public const STATUS_WORDS = ['not_started' => 'Not started', 'in_progress' => 'Writing', 'submitted' => 'Handed in', 'timed_out' => 'Time ran out'];

    private function row(CbtTest $t): array
    {
        return [
            'id' => $t->id, 'title' => $t->title, 'audience' => $t->audience, 'status' => $t->status,
            'class' => $t->schoolClass?->name, 'subject' => $t->subject?->name, 'term' => $t->term?->name,
            'duration_minutes' => $t->duration_minutes, 'questions' => $t->questionTotal(),
            'opens_at' => $t->opens_at?->toIso8601String(), 'closes_at' => $t->closes_at?->toIso8601String(),
            'pass_mark' => $t->pass_mark, 'has_code' => (bool) $t->access_code, 'show_score' => $t->show_score,
            'max_page_leaves' => $t->max_page_leaves,
        ];
    }

    private function form(?CbtTest $test): Response
    {
        $banks = CbtBank::orderBy('name')->get(['id', 'name']);
        $questions = CbtQuestion::whereIn('cbt_bank_id', $banks->pluck('id'))->get(['cbt_bank_id', 'topic', 'difficulty']);

        return Inertia::render('SchoolAdmin/Cbt/TestForm', [
            'test' => $test ? [
                'id' => $test->id, 'title' => $test->title, 'class_id' => $test->class_id, 'subject_id' => $test->subject_id, 'term_id' => $test->term_id,
                'instructions' => $test->instructions, 'duration_minutes' => $test->duration_minutes,
                'opens_at' => $test->opens_at->toIso8601String(), 'closes_at' => $test->closes_at?->toIso8601String(),
                'shuffle_questions' => $test->shuffle_questions, 'shuffle_options' => $test->shuffle_options, 'show_score' => $test->show_score,
                'access_code' => $test->access_code, 'max_page_leaves' => $test->max_page_leaves, 'pass_mark' => $test->pass_mark,
                'sections' => $test->sections->map(fn ($s) => $s->only('title', 'cbt_bank_id', 'topic', 'difficulty', 'question_count'))->values(),
                'started' => $test->attempts()->exists(),
            ] : null,
            // How many questions each bank has, by topic and difficulty, so the form can show what is available
            'banks' => $banks->map(fn (CbtBank $b) => [
                'id' => $b->id, 'name' => $b->name,
                'counts' => $questions->where('cbt_bank_id', $b->id)->map(fn ($q) => ['topic' => $q->topic, 'difficulty' => $q->difficulty])->values(),
            ]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::get(['id', 'name', 'class_id']),
            'terms' => Term::with('academicYear:id,name')->orderByDesc('start_date')->get()
                ->map(fn (Term $t) => ['id' => $t->id, 'name' => trim($t->name.' '.($t->academicYear?->name ?? '')), 'is_current' => $t->is_current]),
        ]);
    }

    private function validated(Request $request): array
    {
        $school = $request->user()->school_id;
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $school)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $school)->where('class_id', $request->class_id)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $school)],
            'instructions' => 'nullable|string|max:3000',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'opens_at' => 'required|date',
            'closes_at' => 'nullable|date|after:opens_at',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_score' => 'boolean',
            'access_code' => 'nullable|string|max:12|alpha_num',
            'max_page_leaves' => 'nullable|integer|min:1|max:50',
            'pass_mark' => 'nullable|numeric|min:0|max:100',
            'sections' => 'required|array|min:1|max:20',
            'sections.*.title' => 'required|string|max:120',
            'sections.*.cbt_bank_id' => ['required', Rule::exists('cbt_banks', 'id')->where('school_id', $school)->whereNull('deleted_at')],
            'sections.*.topic' => 'nullable|string|max:120',
            'sections.*.difficulty' => ['nullable', Rule::in(CbtQuestion::DIFFICULTIES)],
            'sections.*.question_count' => 'required|integer|min:1|max:500',
        ], [
            'sections.required' => 'Add at least one section of questions.',
            'subject_id.exists' => 'Choose a subject of the chosen class.',
            'closes_at.after' => 'The closing time must be after the opening time.',
            'access_code.alpha_num' => 'Use only letters and numbers for the start code.',
        ]);

        return [
            'test' => collect($data)->except('sections')->all() + ['audience' => 'students'],
            'sections' => $data['sections'],
        ];
    }

    private function saveSections(CbtTest $test, array $sections): void
    {
        $test->sections()->delete();
        foreach (array_values($sections) as $i => $s) {
            $test->sections()->create($s + ['position' => $i]);
        }
    }
}
