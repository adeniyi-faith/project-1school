<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShowsCbtSitting;
use App\Models\CbtAttempt;
use App\Models\CbtTest;
use App\Models\Student;
use App\Services\CbtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Students taking computer-based tests in their portal */
class StudentCbtController extends Controller
{
    use ShowsCbtSitting;

    public function __construct(private CbtService $cbt) {}

    public function index(Request $request): Response
    {
        $student = $this->student($request);
        if (! $student) {
            return Inertia::render('Student/Cbt/Index', ['linked' => false, 'tests' => []]);
        }
        $attempts = CbtAttempt::where('student_id', $student->id)->get()->keyBy('cbt_test_id');
        $tests = CbtTest::with('subject:id,name', 'sections')
            ->where(fn ($q) => $q->where('class_id', $student->class_id)->where('audience', 'students')->whereIn('status', ['published', 'closed']))
            ->orWhereIn('id', $attempts->keys())
            ->orderByDesc('opens_at')->limit(100)->get();

        return Inertia::render('Student/Cbt/Index', [
            'linked' => true,
            'tests' => $tests->map(function (CbtTest $t) use ($attempts) {
                $a = $attempts->get($t->id);
                if ($a) {
                    $this->cbt->settle($a);
                }

                return [
                    'id' => $t->id, 'title' => $t->title, 'subject' => $t->subject?->name, 'duration_minutes' => $t->duration_minutes,
                    'questions' => $t->questionTotal(), 'opens_at' => $t->opens_at->toIso8601String(), 'closes_at' => $t->closes_at?->toIso8601String(),
                    'state' => $a ? ($a->isFinished() ? 'done' : 'writing') : ($t->isOpen() ? 'open' : ($t->opens_at->isFuture() && $t->status === 'published' ? 'soon' : 'missed')),
                    'marking' => (bool) $a?->needs_marking,
                    'score' => $a && $a->isFinished() && ! $a->needs_marking && $t->show_score ? $a->score : null,
                    'max_score' => $a && $a->isFinished() && ! $a->needs_marking && $t->show_score ? $a->max_score : null,
                ];
            })->values(),
        ]);
    }

    /** The page before starting: instructions, and the start code when the test needs one */
    public function show(Request $request, CbtTest $test): Response|RedirectResponse
    {
        $student = $this->allowed($request, $test);
        $attempt = CbtAttempt::where('cbt_test_id', $test->id)->where('student_id', $student->id)->first();
        if ($attempt) {
            return redirect($attempt->isFinished() ? "/school/student/cbt/{$test->id}/done" : "/school/student/cbt/{$test->id}/exam");
        }
        return Inertia::render('Student/Cbt/Start', $this->startProps($test, "/school/student/cbt/{$test->id}"));
    }

    public function start(Request $request, CbtTest $test): RedirectResponse
    {
        $student = $this->allowed($request, $test);
        $this->cbt->start($test->load('sections'), $student, $request->input('code'));

        return redirect("/school/student/cbt/{$test->id}/exam");
    }

    public function exam(Request $request, CbtTest $test): Response|RedirectResponse
    {
        $attempt = $this->attempt($request, $test);
        if ($attempt->isFinished()) {
            return redirect("/school/student/cbt/{$test->id}/done");
        }
        $s = $attempt->student;

        return Inertia::render('Student/Cbt/Exam', $this->examProps($test, $attempt, "/school/student/cbt/{$test->id}", [
            'name' => $s ? trim($s->first_name.' '.$s->last_name) : '', 'number' => $s?->admission_no,
        ]));
    }

    /** Saves one answer straight away; the exam page calls this on every click */
    public function answer(Request $request, CbtTest $test): JsonResponse
    {
        $attempt = $this->attempt($request, $test);
        $data = $request->validate(['question_id' => 'required|integer', 'option' => 'nullable|integer|min:0', 'text' => 'nullable|string|max:20000'],
            ['text.max' => 'That answer is too long. Keep it under 20,000 characters.']);
        if ($request->exists('text')) {
            $this->cbt->write($attempt, $data['question_id'], $data['text'] ?? null);
        } else {
            $this->cbt->answer($attempt, $data['question_id'], $data['option'] ?? null);
        }

        return $this->state($attempt);
    }

    public function flag(Request $request, CbtTest $test): JsonResponse
    {
        $attempt = $this->attempt($request, $test);
        $data = $request->validate(['question_id' => 'required|integer', 'on' => 'required|boolean']);
        $this->cbt->flag($attempt, $data['question_id'], $data['on']);

        return $this->state($attempt);
    }

    /** The exam page reports each time the student leaves it */
    public function left(Request $request, CbtTest $test): JsonResponse
    {
        $attempt = $this->cbt->leftPage($this->attempt($request, $test)->load('test'));

        return $this->state($attempt);
    }

    /** The timer checks in now and then, so the clock on screen follows the server */
    public function ping(Request $request, CbtTest $test): JsonResponse
    {
        return $this->state($this->attempt($request, $test));
    }

    public function submit(Request $request, CbtTest $test): RedirectResponse
    {
        $attempt = $this->attempt($request, $test);
        $this->cbt->settle($attempt);
        $this->cbt->finish($attempt);

        return redirect("/school/student/cbt/{$test->id}/done");
    }

    public function done(Request $request, CbtTest $test): Response|RedirectResponse
    {
        $attempt = $this->attempt($request, $test);
        if (! $attempt->isFinished()) {
            return redirect("/school/student/cbt/{$test->id}/exam");
        }
        return Inertia::render('Student/Cbt/Done', $this->doneProps($test, $attempt, "/school/student/cbt/{$test->id}"));
    }

    private function student(Request $request): ?Student
    {
        return Student::where('user_id', $request->user()->id)->first();
    }

    /** The student, if this test is for their class (or they have already sat it) */
    private function allowed(Request $request, CbtTest $test): Student
    {
        $student = $this->student($request);
        abort_unless($student, 404);
        $sat = CbtAttempt::where('cbt_test_id', $test->id)->where('student_id', $student->id)->exists();
        abort_unless($sat || ($test->audience === 'students' && $test->class_id === $student->class_id && $test->status !== 'draft'), 404);

        return $student;
    }

    private function attempt(Request $request, CbtTest $test): CbtAttempt
    {
        $student = $this->student($request);
        abort_unless($student, 404);
        $attempt = CbtAttempt::with('student')->where('cbt_test_id', $test->id)->where('student_id', $student->id)->firstOrFail();
        $this->cbt->settle($attempt);

        return $attempt;
    }
}
