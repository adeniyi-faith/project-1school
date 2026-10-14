<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Services\ReportCardService;
use App\Services\StudentLoginService;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Sign-in details for many students (and parents) at once. The work is done in small steps that the page
 * asks for one after another, so a big school never waits on one long request. The passwords are kept
 * (scrambled) for two hours only so the sheet can be printed; after that they can only be replaced.
 */
class StudentLoginController extends Controller
{
    private const STEP = 20;

    public function __construct(private StudentLoginService $logins) {}

    public function index(): Response
    {
        $sid = $this->getSchoolId();
        $students = Student::where('school_id', $sid)->where('status', 'active')->get(['id', 'class_id', 'user_id', 'guardian_id']);

        return Inertia::render('SchoolAdmin/Students/Logins', [
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name'])->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name,
                'students' => $students->where('class_id', $c->id)->count(),
                'with_login' => $students->where('class_id', $c->id)->whereNotNull('user_id')->count(),
            ]),
            'sections' => Section::where('school_id', $sid)->orderBy('name')->get(['id', 'class_id', 'name']),
            'totals' => ['students' => $students->count(), 'with_login' => $students->whereNotNull('user_id')->count()],
        ]);
    }

    /** Decide who is covered and open a run; the page then asks for the steps */
    public function start(Request $request): JsonResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'class_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('classes', 'id')->where('school_id', $sid)],
            'section_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('sections', 'id')->where('school_id', $sid)],
            'parents' => 'boolean',
            'reset' => 'boolean',
        ]);

        $ids = $this->logins->targets($sid, $data['class_id'] ?? null, $data['section_id'] ?? null);
        if ($ids === []) {
            throw ValidationException::withMessages(['class_id' => 'There are no active students here.']);
        }

        $token = (string) Str::uuid();
        $this->save($sid, $request->user()->id, $token, [
            'pending' => $ids, 'parents' => $request->boolean('parents'), 'reset' => $request->boolean('reset'),
            'lines' => [], 'skipped' => 0, 'problems' => [], 'total' => count($ids),
        ]);

        return response()->json(['token' => $token, 'total' => count($ids)]);
    }

    /** Make the next few logins */
    public function step(Request $request, string $token): JsonResponse
    {
        $sid = $this->getSchoolId();
        $run = $this->load($sid, $request->user()->id, $token);

        $chunk = array_splice($run['pending'], 0, self::STEP);
        $result = $this->logins->run($sid, $chunk, $run['parents'], $run['reset']);
        $run['lines'] = array_merge($run['lines'], $result['lines']);
        $run['skipped'] += $result['skipped'];
        $run['problems'] = array_merge($run['problems'], $result['problems']);
        $this->save($sid, $request->user()->id, $token, $run);

        $finished = $run['pending'] === [];
        if ($finished) {
            activity('users')->causedBy($request->user())->withProperties(['school_id' => $sid, 'made' => count($run['lines']), 'reset' => $run['reset']])->log('Login details made in bulk');
        }

        return response()->json([
            'done' => $run['total'] - count($run['pending']), 'total' => $run['total'], 'finished' => $finished,
            'made' => count($run['lines']), 'skipped' => $run['skipped'], 'problems' => array_slice($run['problems'], 0, 20),
        ]);
    }

    public function pdf(Request $request, string $token, ReportCardService $cards): HttpResponse
    {
        $sid = $this->getSchoolId();
        $run = $this->load($sid, $request->user()->id, $token);

        return Pdf::loadView('student-logins.slips', [
            'lines' => $run['lines'], 'school' => $cards->school($sid), 'loginUrl' => url('/login'),
        ])->setPaper('a4')->stream('login-details.pdf');
    }

    public function excel(Request $request, string $token): HttpResponse
    {
        $run = $this->load($this->getSchoolId(), $request->user()->id, $token);
        $rows = [['Type', 'Name', 'Class', 'Admission number', 'Username', 'Password']];
        foreach ($run['lines'] as $l) {
            $rows[] = [$l['role'], $l['name'], $l['class'], $l['admission_no'], $l['username'], $l['password']];
        }

        return response((new SimpleXlsx)->sheet('Login details', $rows, ['bold_rows' => [0], 'freeze_rows' => 1, 'widths' => [0 => 10, 1 => 28, 2 => 26, 3 => 18, 4 => 38, 5 => 14]])->bytes(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="login-details.xlsx"',
        ]);
    }

    private function key(int $schoolId, int $userId, string $token): string
    {
        return "student-logins:{$schoolId}:{$userId}:{$token}";
    }

    private function save(int $schoolId, int $userId, string $token, array $run): void
    {
        Cache::put($this->key($schoolId, $userId, $token), Crypt::encryptString(json_encode($run)), now()->addHours(2));
    }

    private function load(int $schoolId, int $userId, string $token): array
    {
        $raw = Cache::get($this->key($schoolId, $userId, $token));
        $run = $raw ? json_decode(Crypt::decryptString($raw), true) : null;
        abort_unless(is_array($run), 404, 'This list has expired. Make the login details again.');

        return $run;
    }
}
