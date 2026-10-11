<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Subjects, each offered by as many classes as need it. In senior classes a subject can be limited to
 * some departments (Physics for Science arms only). Results and scores always stay with the subject.
 */
class SubjectController extends Controller
{
    public function index(): Response
    {
        $sid = $this->getSchoolId();
        $subjects = Subject::with('classLines')->orderBy('name')->get();

        return Inertia::render('SchoolAdmin/Subjects/Index', [
            'subjects' => $subjects->map(fn (Subject $s) => [
                'id' => $s->id, 'name' => $s->name, 'code' => $s->code, 'type' => $s->type,
                'full_marks' => (int) $s->full_marks, 'pass_marks' => (int) $s->pass_marks,
                'classes' => $s->classLines->map(fn (ClassSubject $l) => ['id' => (int) $l->class_id, 'departments' => $l->departments ?? []])->values(),
            ]),
            'classes' => SchoolClass::orderBy('numeric_name')->orderBy('name')->get(['id', 'name', 'level']),
            'levels' => SchoolClass::LEVELS,
            'departments' => SectionController::departments($sid),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        DB::transaction(function () use ($data) {
            $subject = Subject::create(collect($data)->except('classes')->all() + ['class_id' => $data['classes'][0]['id'] ?? null]);
            $this->syncClasses($subject, $data['classes']);
        });

        return back()->with('success', 'Subject added.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $data = $this->validated($request, $subject);

        DB::transaction(function () use ($data, $subject) {
            $subject->update(collect($data)->except('classes')->all());
            $this->syncClasses($subject, $data['classes']);
        });

        return back()->with('success', 'Subject saved.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', 'Subject deleted. Results already given in it are kept.');
    }

    /** The classes that offer the subject become exactly these; a class kept keeps its own score setup */
    private function syncClasses(Subject $subject, array $classes): void
    {
        $keep = collect($classes)->pluck('id')->map(fn ($id) => (int) $id);
        ClassSubject::where('subject_id', $subject->id)->whereNotIn('class_id', $keep)->delete();
        $subject->offerIn($keep->all(), collect($classes)->mapWithKeys(fn ($c) => [(int) $c['id'] => $c['departments'] ?? []])->all());
    }

    private function validated(Request $request, ?Subject $subject): array
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'type' => 'required|in:theory,practical',
            'full_marks' => 'nullable|integer|min:1|max:1000',
            'pass_marks' => 'nullable|integer|min:1|max:1000',
            'classes' => 'present|array',
            'classes.*.id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'classes.*.departments' => 'nullable|array',
            'classes.*.departments.*' => 'string|max:60',
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));
        $same = Subject::where('school_id', $sid)->when($subject, fn ($q) => $q->whereKeyNot($subject->id))
            ->get(['id', 'name'])->first(fn ($s) => mb_strtolower(trim($s->name)) === mb_strtolower($name));
        if ($same) {
            throw ValidationException::withMessages(['name' => "There is already a subject called {$same->name}. Open it and tick the classes that should have it."]);
        }
        if (($data['pass_marks'] ?? 0) > ($data['full_marks'] ?? 100)) {
            throw ValidationException::withMessages(['pass_marks' => 'The pass mark cannot be more than the full marks.']);
        }

        return ['name' => $name] + $data;
    }
}
