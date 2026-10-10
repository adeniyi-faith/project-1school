<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ShowsCbtSitting;
use App\Models\CbtAttempt;
use App\Models\CbtCandidate;
use App\Models\CbtQuestion;
use App\Models\CbtTest;
use App\Services\CbtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Applicants writing an entrance exam. They have no account: they log in at /exam with the
 * exam number and PIN printed on their slip, and that sitting is all they can reach.
 */
class CandidateCbtController extends Controller
{
    use ShowsCbtSitting;

    private const SESSION = 'cbt_candidate';
    private const BASE = '/exam';

    public function __construct(private CbtService $cbt) {}

    public function login(Request $request): Response|RedirectResponse
    {
        if ($this->candidate($request)) {
            return redirect(self::BASE.'/start');
        }

        return Inertia::render('Exam/Login');
    }

    public function signIn(Request $request): RedirectResponse
    {
        $data = $request->validate(['exam_number' => 'required|string|max:20', 'pin' => 'required|string|max:12']);
        $candidate = CbtCandidate::withoutGlobalScopes()->with('test')
            ->where('exam_number', strtoupper(trim($data['exam_number'])))->first();

        $ok = $candidate && $candidate->test && $candidate->test->status !== 'draft'
            && hash_equals((string) $candidate->pin, preg_replace('/\s+/', '', $data['pin']));
        if (! $ok) {
            throw ValidationException::withMessages(['exam_number' => 'That exam number and PIN do not match. Check your slip and try again.']);
        }

        // An exam computer must not stay signed in to a staff or student account
        if (Auth::check()) {
            Auth::guard('web')->logout();
        }
        $request->session()->regenerate();
        $request->session()->put(self::SESSION, $candidate->id);

        return redirect(self::BASE.'/start');
    }

    public function signOut(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION);
        $request->session()->regenerate();

        return redirect(self::BASE);
    }

    /** Instructions, and the start code when the exam needs one */
    public function show(Request $request): Response|RedirectResponse
    {
        $candidate = $this->mustHave($request);
        if ($attempt = $this->attemptOf($candidate)) {
            return redirect(self::BASE.($attempt->isFinished() ? '/done' : '/paper'));
        }

        return Inertia::render('Student/Cbt/Start', $this->startProps($candidate->test, self::BASE) + ['person' => $this->person($candidate)]);
    }

    public function start(Request $request): RedirectResponse
    {
        $candidate = $this->mustHave($request);
        $this->cbt->start($candidate->test->load('sections'), $candidate, $request->input('code'));

        return redirect(self::BASE.'/paper');
    }

    public function exam(Request $request): Response|RedirectResponse
    {
        $candidate = $this->mustHave($request);
        $attempt = $this->sitting($candidate);
        if ($attempt->isFinished()) {
            return redirect(self::BASE.'/done');
        }

        return Inertia::render('Student/Cbt/Exam', $this->examProps($candidate->test, $attempt, self::BASE, $this->person($candidate), self::BASE.'/images'));
    }

    public function answer(Request $request): JsonResponse
    {
        $attempt = $this->sitting($this->mustHave($request));
        $data = $request->validate(['question_id' => 'required|integer', 'option' => 'nullable|integer|min:0']);
        $this->cbt->answer($attempt, $data['question_id'], $data['option']);

        return $this->state($attempt);
    }

    public function flag(Request $request): JsonResponse
    {
        $attempt = $this->sitting($this->mustHave($request));
        $data = $request->validate(['question_id' => 'required|integer', 'on' => 'required|boolean']);
        $this->cbt->flag($attempt, $data['question_id'], $data['on']);

        return $this->state($attempt);
    }

    public function left(Request $request): JsonResponse
    {
        return $this->state($this->cbt->leftPage($this->sitting($this->mustHave($request))->load('test')));
    }

    public function ping(Request $request): JsonResponse
    {
        return $this->state($this->sitting($this->mustHave($request)));
    }

    public function submit(Request $request): RedirectResponse
    {
        $attempt = $this->sitting($this->mustHave($request));
        $this->cbt->finish($attempt);

        return redirect(self::BASE.'/done');
    }

    public function done(Request $request): Response|RedirectResponse
    {
        $candidate = $this->mustHave($request);
        $attempt = $this->sitting($candidate);
        if (! $attempt->isFinished()) {
            return redirect(self::BASE.'/paper');
        }

        return Inertia::render('Student/Cbt/Done', $this->doneProps($candidate->test, $attempt, self::BASE) + ['person' => $this->person($candidate)]);
    }

    /** Pictures on the questions of this applicant's own paper */
    public function image(Request $request, int $question): HttpResponse
    {
        $attempt = $this->attemptOf($this->mustHave($request));
        abort_unless($attempt && collect($attempt->paper)->contains('q', $question), 404);
        $q = CbtQuestion::withoutGlobalScopes()->withTrashed()->findOrFail($question);
        abort_unless($q->image_path && Storage::disk('private')->exists($q->image_path), 404);

        return Storage::disk('private')->response($q->image_path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    private function candidate(Request $request): ?CbtCandidate
    {
        $id = $request->session()->get(self::SESSION);
        if (! $id) {
            return null;
        }
        $candidate = CbtCandidate::withoutGlobalScopes()->with(['test' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at')])->find($id);

        return $candidate?->test ? $candidate : null;
    }

    private function mustHave(Request $request): CbtCandidate
    {
        $candidate = $this->candidate($request);
        if (! $candidate) {
            // Signed out, or the exam was removed: back to the login page
            abort(redirect(self::BASE)->with('error', 'Please log in with your exam number and PIN.'));
        }

        return $candidate;
    }

    private function attemptOf(CbtCandidate $candidate): ?CbtAttempt
    {
        return CbtAttempt::withoutGlobalScopes()->where('cbt_test_id', $candidate->cbt_test_id)->where('cbt_candidate_id', $candidate->id)->first();
    }

    private function sitting(CbtCandidate $candidate): CbtAttempt
    {
        $attempt = $this->attemptOf($candidate);
        abort_unless($attempt, 404);
        $attempt->setRelation('test', $candidate->test);
        $this->cbt->settle($attempt);

        return $attempt;
    }

    private function person(CbtCandidate $candidate): array
    {
        $candidate->loadMissing(['inquiry' => fn ($q) => $q->withoutGlobalScopes()]);

        return ['name' => $candidate->inquiry?->student_name ?? '', 'number' => $candidate->exam_number];
    }
}
