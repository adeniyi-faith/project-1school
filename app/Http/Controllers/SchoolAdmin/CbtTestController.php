<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtBank;
use App\Models\CbtCandidate;
use App\Models\CbtQuestion;
use App\Models\CbtTest;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Services\CbtService;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $data = $this->validated($request, $test);
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
        $test->update(['status' => $status]);
        if ($status === 'closed') {
            $this->cbt->settleTest($test);
            $this->cbt->markAbsent($test);
        }

        return back()->with('success', match ($status) {
            'published' => $test->audience === 'applicants'
                ? 'The exam is open. Applicants log in at '.url('/exam').' with the exam number and PIN on their slip, from its opening time.'
                : 'The test is open. Students in the class can start it in their portal from its opening time.',
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
                'sections' => $test->sections->map(fn ($s) => ['title' => $s->title, 'bank' => $s->bank?->name, 'topic' => $s->topic, 'difficulty' => $s->difficulty, 'question_type' => $s->question_type, 'count' => $s->question_count]),
            ],
            'problems' => $test->status === 'draft' ? $this->cbt->problems($test) : [],
            'results' => $results->values(),
            'summary' => [
                'expected' => $results->count(),
                'finished' => $finished->count(),
                'writing' => $results->where('status', 'in_progress')->count(),
                'to_mark' => $results->where('needs_marking', true)->count(),
                'average' => $finished->whereNotNull('percent')->count() ? round($finished->whereNotNull('percent')->avg('percent'), 1) : null,
                'highest' => $finished->max('percent'),
                'lowest' => $finished->min('percent'),
                'passed' => $test->pass_mark !== null ? $finished->where('percent', '>=', $test->pass_mark)->count() : null,
            ],
            'questionReport' => $this->cbt->questionReport($test),
            'scoreParts' => $scheme ? $scheme->components->map(fn ($c) => ['id' => $c->id, 'label' => "{$c->name} (out of {$c->max_score})"])->values() : [],
            'canManage' => $request->user()->can('cbt.manage'),
            'canCopy' => $test->audience === 'students' && $request->user()->can('marks.entry'),
            // Applicants who can still be put down for this entrance exam
            'applicants' => $test->audience === 'applicants'
                ? AdmissionInquiry::whereNotIn('status', ['admitted', 'dropped'])->whereNotIn('id', $test->candidates()->select('inquiry_id'))
                    ->orderBy('student_name')->limit(500)->get(['id', 'student_name', 'class_interested', 'guardian_phone'])
                : [],
            'examUrl' => url('/exam'),
        ]);
    }

    /** Put applicants down for an entrance exam */
    public function addCandidates(Request $request, CbtTest $test): RedirectResponse
    {
        $ids = $request->validate([
            'inquiry_ids' => 'required|array|min:1|max:500',
            'inquiry_ids.*' => ['integer', Rule::exists('admission_inquiries', 'id')->where('school_id', $request->user()->school_id)->whereNull('deleted_at')],
        ], ['inquiry_ids.required' => 'Choose at least one applicant.'])['inquiry_ids'];
        $count = $this->cbt->addCandidates($test, $ids, $request->user()->id);

        return back()->with('success', "{$count} applicant(s) added. Print their exam slips: each has an exam number and a PIN to log in with.");
    }

    public function removeCandidate(CbtTest $test, CbtCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->cbt_test_id === $test->id, 404);
        $this->cbt->removeCandidate($candidate);

        return back()->with('success', 'Applicant taken off this exam.');
    }

    /** A new PIN for an applicant who lost their slip */
    public function newPin(CbtTest $test, CbtCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->cbt_test_id === $test->id, 404);
        $this->cbt->newPinFor($candidate);

        return back()->with('success', 'New PIN made. Print their slip again; the old PIN no longer works.');
    }

    /** Exam slips to print and hand out: one applicant, or all of them, several to a page */
    public function slips(Request $request, CbtTest $test): HttpResponse
    {
        abort_unless($test->audience === 'applicants', 404);
        $candidates = $test->candidates()->with('inquiry:id,student_name,class_interested')
            ->when($request->integer('candidate'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('exam_number')->get();
        abort_if($candidates->isEmpty(), 404);

        $bytes = Pdf::loadView('cbt.slips', [
            'test' => $test, 'school' => School::find($test->school_id), 'candidates' => $candidates, 'examUrl' => url('/exam'),
        ])->setPaper('a4', 'portrait')->output();

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::slug($test->title).'-exam-slips.pdf"',
        ]);
    }

    public function export(CbtTest $test): HttpResponse
    {
        $test->load('sections');
        $results = $this->cbt->results($test);
        $attempts = $test->attempts()->get()->keyBy('id');
        $titles = $test->sections->pluck('title')->all();
        $applicants = $test->audience === 'applicants';
        $rows = [array_merge([$applicants ? 'Exam no.' : 'Admission no.', 'Name', 'Status', 'Score', 'Out of', 'Percent'], $titles, ['Answered', 'Left the page', 'Minutes'], $applicants ? ['Outcome'] : [])];
        foreach ($results as $r) {
            $a = $r['attempt_id'] ? $attempts->get($r['attempt_id']) : null;
            $sections = array_map(fn ($i) => isset($a?->section_scores[$i]) ? $a->section_scores[$i]['score'].' / '.$a->section_scores[$i]['max'] : '', array_keys($titles));
            $rows[] = array_merge([$r['admission_no'], $r['name'], self::STATUS_WORDS[$r['status']].($r['needs_marking'] ? ' (theory to mark)' : ''), $r['score'], $r['max_score'], $r['percent']], $sections,
                [$r['answered'].' of '.$r['questions'], $a?->page_leaves ?? '', $r['minutes']], $applicants ? [ucfirst((string) $r['outcome'])] : []);
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
        $this->cbt->resetAdmission($attempt);
        $attempt->delete();

        return back()->with('success', 'Their sitting was removed. They can start the test again with a new set of questions.');
    }

    public function copyToResults(Request $request, CbtTest $test): RedirectResponse
    {
        $id = (int) $request->validate(['component_id' => 'required|integer'])['component_id'];
        $count = $this->cbt->copyToResults($test->load('subject'), $id);
        $waiting = $test->attempts()->where('needs_marking', true)->count();

        return back()->with('success', "{$count} score(s) were put into the term results."
            .($waiting ? " {$waiting} student(s) still have theory answers to mark; send the scores again once they are marked." : ''));
    }

    /** Mark typed (theory) answers: one question at a time, every student's answer to it */
    public function marking(Request $request, CbtTest $test): Response
    {
        $sheet = $this->cbt->markingSheet($test, $request->integer('question') ?: null);

        return Inertia::render('SchoolAdmin/Cbt/Marking', $sheet + [
            'test' => ['id' => $test->id, 'title' => $test->title, 'audience' => $test->audience],
        ]);
    }

    public function saveMarks(Request $request, CbtTest $test): RedirectResponse
    {
        $data = $request->validate([
            'question_id' => 'required|integer',
            'marks' => 'required|array|min:1|max:1000',
            'marks.*.marks' => 'required|numeric|min:0|max:100',
            'marks.*.comment' => 'nullable|string|max:500',
        ], ['marks.*.marks.required' => 'Give a mark for every answer you are saving.']);
        $saved = $this->cbt->markTheory($test, $data['question_id'], $data['marks']);

        return back()->with('success', "{$saved} answer(s) marked.");
    }

    public const STATUS_WORDS = ['not_started' => 'Not started', 'in_progress' => 'Writing', 'submitted' => 'Handed in', 'timed_out' => 'Time ran out'];

    private function row(CbtTest $t): array
    {
        return [
            'id' => $t->id, 'title' => $t->title, 'audience' => $t->audience, 'status' => $t->status,
            'class' => $t->schoolClass?->name, 'subject' => $t->subject?->name, 'term' => $t->term?->name,
            'duration_minutes' => $t->duration_minutes, 'questions' => $t->questionTotal(),
            'opens_at' => $t->opens_at?->toIso8601String(), 'closes_at' => $t->closes_at?->toIso8601String(),
            'pass_mark' => $t->pass_mark, 'has_code' => (bool) $t->access_code, 'show_score' => $t->show_score, 'allow_calculator' => $t->allow_calculator,
            'max_page_leaves' => $t->max_page_leaves,
        ];
    }

    private function form(?CbtTest $test): Response
    {
        $banks = CbtBank::orderBy('name')->get(['id', 'name']);
        $questions = CbtQuestion::whereIn('cbt_bank_id', $banks->pluck('id'))->get(['cbt_bank_id', 'topic', 'difficulty', 'type']);

        return Inertia::render('SchoolAdmin/Cbt/TestForm', [
            'test' => $test ? [
                'id' => $test->id, 'title' => $test->title, 'audience' => $test->audience, 'class_id' => $test->class_id, 'subject_id' => $test->subject_id, 'term_id' => $test->term_id,
                'instructions' => $test->instructions, 'duration_minutes' => $test->duration_minutes,
                'opens_at' => $test->opens_at->toIso8601String(), 'closes_at' => $test->closes_at?->toIso8601String(),
                'shuffle_questions' => $test->shuffle_questions, 'shuffle_options' => $test->shuffle_options, 'show_score' => $test->show_score,
                'allow_calculator' => $test->allow_calculator, 'access_code' => $test->access_code, 'max_page_leaves' => $test->max_page_leaves, 'pass_mark' => $test->pass_mark,
                'sections' => $test->sections->map(fn ($s) => $s->only('title', 'cbt_bank_id', 'topic', 'difficulty', 'question_type', 'question_count'))->values(),
                'started' => $test->attempts()->exists(),
                'has_candidates' => $test->candidates()->exists(),
            ] : null,
            // How many questions each bank has, by topic and difficulty, so the form can show what is available
            'banks' => $banks->map(fn (CbtBank $b) => [
                'id' => $b->id, 'name' => $b->name,
                'counts' => $questions->where('cbt_bank_id', $b->id)->map(fn ($q) => ['topic' => $q->topic, 'difficulty' => $q->difficulty, 'type' => $q->type])->values(),
            ]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::get(['id', 'name', 'class_id']),
            'terms' => Term::with('academicYear:id,name')->orderByDesc('start_date')->get()
                ->map(fn (Term $t) => ['id' => $t->id, 'name' => trim($t->name.' '.($t->academicYear?->name ?? '')), 'is_current' => $t->is_current]),
        ]);
    }

    private function validated(Request $request, ?CbtTest $test = null): array
    {
        $school = $request->user()->school_id;
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'audience' => ['nullable', Rule::in(['students', 'applicants'])],
            'class_id' => [Rule::requiredIf($request->input('audience', 'students') === 'students'), 'nullable', Rule::exists('classes', 'id')->where('school_id', $school)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $school)->where('class_id', $request->class_id)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $school)],
            'instructions' => 'nullable|string|max:3000',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'opens_at' => 'required|date',
            'closes_at' => 'nullable|date|after:opens_at',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_score' => 'boolean',
            'allow_calculator' => 'boolean',
            'access_code' => 'nullable|string|max:12|alpha_num',
            'max_page_leaves' => 'nullable|integer|min:1|max:50',
            'pass_mark' => 'nullable|numeric|min:0|max:100',
            'sections' => 'required|array|min:1|max:20',
            'sections.*.title' => 'required|string|max:120',
            'sections.*.cbt_bank_id' => ['required', Rule::exists('cbt_banks', 'id')->where('school_id', $school)->whereNull('deleted_at')],
            'sections.*.topic' => 'nullable|string|max:120',
            'sections.*.difficulty' => ['nullable', Rule::in(CbtQuestion::DIFFICULTIES)],
            'sections.*.question_type' => ['nullable', Rule::in(CbtQuestion::TYPES)],
            'sections.*.question_count' => 'required|integer|min:1|max:500',
        ], [
            'sections.required' => 'Add at least one section of questions.',
            'subject_id.exists' => 'Choose a subject of the chosen class.',
            'class_id.required' => 'Choose the class that sits this test.',
            'closes_at.after' => 'The closing time must be after the opening time.',
            'access_code.alpha_num' => 'Use only letters and numbers for the start code.',
        ]);

        $data['audience'] ??= 'students';
        if ($test && $test->audience !== $data['audience'] && ($test->attempts()->exists() || $test->candidates()->exists())) {
            throw ValidationException::withMessages(['audience' => 'People are already down for this test, so who sits it cannot be changed.']);
        }
        if ($data['audience'] === 'applicants') {
            // An entrance exam is not for a class, so it has no class, subject or term results
            $data = array_merge($data, ['class_id' => null, 'subject_id' => null, 'term_id' => null]);
        }

        return [
            'test' => collect($data)->except('sections')->all(),
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
