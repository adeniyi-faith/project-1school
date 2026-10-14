<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Adding many students at once: download the template, upload it, check the preview, add the good rows */
class StudentImportController extends Controller
{
    public function __construct(private StudentImportService $import) {}

    public function index(): Response
    {
        $sid = $this->getSchoolId();

        return Inertia::render('SchoolAdmin/Students/Import', [
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name'])->map(fn ($c) => [
                'name' => $c->name,
                'arms' => Section::where('class_id', $c->id)->orderBy('name')->pluck('name'),
            ]),
            'maxRows' => StudentImportService::MAX_ROWS,
        ]);
    }

    public function template(Request $request): HttpResponse
    {
        if ($request->query('format') === 'csv') {
            return response($this->import->templateCsv(), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="students-template.csv"',
            ]);
        }

        return response($this->import->template($this->getSchoolId()), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="students-template.xlsx"',
        ]);
    }

    /** Read and check the uploaded file. Nothing is saved; the checked rows are kept for an hour for the next step. */
    public function preview(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:5120|mimes:xlsx,csv,txt'], [
            'file.mimes' => 'Upload the filled-in template as an Excel (.xlsx) or CSV file.',
            'file.required' => 'Choose the filled-in file first.',
        ]);

        $sid = $this->getSchoolId();
        $checked = $this->import->check($sid, $this->import->read($request->file('file')));

        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($sid, $request->user()->id, $token), $checked['rows'], now()->addHour());

        return response()->json([
            'token' => $token,
            'ok' => $checked['ok'],
            'bad' => $checked['bad'],
            'rows' => array_map(fn ($r) => [
                'line' => $r['line'], 'name' => trim($r['first_name'].' '.$r['last_name']), 'gender' => $r['gender'], 'class' => $r['class'],
                'arm' => $r['arm'], 'date_of_birth' => $r['date_of_birth'], 'parent' => $r['parent_name'], 'phone' => $r['parent_phone'], 'errors' => $r['errors'],
            ], $checked['rows']),
        ]);
    }

    /** Add the rows that passed. The file is checked again first, in case something changed since the preview. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => 'required|uuid']);
        $sid = $this->getSchoolId();
        $key = $this->cacheKey($sid, $request->user()->id, $data['token']);

        $rows = Cache::get($key);
        if (! is_array($rows)) {
            throw ValidationException::withMessages(['token' => 'This upload has expired. Upload the file again.']);
        }

        // Check again from the kept rows (not from errors saved earlier), so a class made since then is found
        $again = $this->import->check($sid, array_map(fn ($r) => $this->raw($r), $rows));
        $added = $this->import->import($sid, $again['rows']);
        Cache::forget($key);

        activity('students')->causedBy($request->user())->withProperties(['school_id' => $sid, 'added' => $added, 'left_out' => $again['bad']])->log('Students imported from a file');

        $message = "{$added} student".($added === 1 ? '' : 's').' added.';
        if ($again['bad'] > 0) {
            $message .= " {$again['bad']} row".($again['bad'] === 1 ? ' was' : 's were').' left out because of problems. Fix them in your file and upload it again.';
        }

        return redirect()->route('school.students.index')->with('success', $message);
    }

    private function cacheKey(int $schoolId, int $userId, string $token): string
    {
        return "student-import:{$schoolId}:{$userId}:{$token}";
    }

    /** A checked row turned back into the text the person typed, so it can be checked again */
    private function raw(array $row): array
    {
        return collect(array_keys(StudentImportService::COLUMNS))->mapWithKeys(fn ($k) => [$k => (string) ($row[$k] ?? '')])->all();
    }
}
