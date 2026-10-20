<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(): Response
    {
        $classes = SchoolClass::withCount(['sections', 'subjects'])
            ->with(['subjectLines' => fn ($q) => $q->with('subject:id,name')->orderBy('id')])
            ->orderBy('numeric_name')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $c) => array_merge($c->toArray(), [
                'subject_teachers' => $c->subjectLines->map(fn (ClassSubject $l) => [
                    'id' => $l->id, 'subject' => $l->subject?->name ?? 'Subject', 'teacher_id' => $l->teacher_id,
                ])->sortBy('subject', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            ]));

        return Inertia::render('SchoolAdmin/Classes/Index', [
            'manager_label' => self::managerLabel($this->getSchoolId()),
            'classes' => $classes,
            'levels' => SchoolClass::LEVELS,
            'staff' => Staff::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn (Staff $s) => ['id' => $s->id, 'name' => $s->full_name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'numeric_name' => 'nullable|integer|min:1',
            'level'        => ['nullable', Rule::in(array_keys(SchoolClass::LEVELS))],
            'capacity'     => 'nullable|integer|min:0',
        ]);

        SchoolClass::create($data);

        return back()->with('success', 'Class created.');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'numeric_name' => 'nullable|integer|min:1',
            'level'        => ['nullable', Rule::in(array_keys(SchoolClass::LEVELS))],
            'capacity'     => 'nullable|integer|min:0',
        ]);

        $class->update($data);

        return back()->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return back()->with('success', 'Class deleted.');
    }

    /** What this school calls the person in charge of a class (Class Teacher, Manager, Form Master ...) */
    public static function managerLabel(int $schoolId): string
    {
        return (string) SchoolSetting::get($schoolId, 'class_teacher_label', 'Class Teacher');
    }

    public function saveLabel(Request $request): RedirectResponse
    {
        $data = $request->validate(['label' => 'required|string|max:40']);
        $label = trim($data['label']);

        SchoolSetting::set($this->getSchoolId(), 'class_teacher_label', $label, 'general');

        return back()->with('success', "Saved. Classes now say \"{$label}\".");
    }

    /** Who is the class teacher, and who teaches each subject of this class */
    public function teachers(Request $request, SchoolClass $class): RedirectResponse
    {
        $sid = $class->school_id;
        $staffRule = Rule::exists('staff', 'id')->where('school_id', $sid)->whereNull('deleted_at');
        $data = $request->validate([
            'class_teacher_staff_id' => ['nullable', $staffRule],
            'subjects'               => 'nullable|array|max:100',
            'subjects.*.id'          => ['required', Rule::exists('class_subjects', 'id')->where('school_id', $sid)->where('class_id', $class->id)],
            'subjects.*.teacher_id'  => ['nullable', $staffRule],
        ]);

        $class->update(['class_teacher_staff_id' => $data['class_teacher_staff_id'] ?? null]);
        foreach ($data['subjects'] ?? [] as $line) {
            ClassSubject::where('class_id', $class->id)->whereKey($line['id'])->update(['teacher_id' => $line['teacher_id'] ?? null]);
        }

        return back()->with('success', "Teachers saved for {$class->name}.");
    }
}
