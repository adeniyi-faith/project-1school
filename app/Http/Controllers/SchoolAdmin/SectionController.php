<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Arms of each class (JSS 1 A, JSS 1 B ... or JSS 1 Emerald, JSS 1 Stars). Stored in the `sections` table. */
class SectionController extends Controller
{
    public const DEPARTMENTS = ['Science', 'Arts', 'Commercial'];

    public function index(): Response
    {
        $classes = SchoolClass::with(['sections' => fn ($q) => $q->withCount(['students' => fn ($s) => $s->where('status', 'active')])->orderBy('name')])
            ->orderBy('numeric_name')
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'numeric_name']);

        return Inertia::render('SchoolAdmin/Sections/Index', [
            'classes' => $classes,
            'departments' => self::departments($this->getSchoolId()),
        ]);
    }

    /** Add one arm, or several at once from a list ("A, B, C" or "Emerald, Stars") */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'names' => 'required|string|max:500',
            'department' => 'nullable|string|max:60',
            'capacity' => 'nullable|integer|min:0|max:1000',
        ], ['names.required' => 'Type the name of the arm, or several names with commas between them.']);

        $names = collect(preg_split('/[,\n]+/', $data['names']))->map(fn ($n) => trim(preg_replace('/\s+/', ' ', $n)))->filter()->unique(fn ($n) => mb_strtolower($n));
        if ($names->isEmpty() || $names->contains(fn ($n) => mb_strlen($n) > 100)) {
            throw ValidationException::withMessages(['names' => 'Give each arm a short name, such as A or Emerald.']);
        }
        $taken = Section::where('class_id', $data['class_id'])->pluck('name')->map(fn ($n) => mb_strtolower($n));
        $new = $names->reject(fn ($n) => $taken->contains(mb_strtolower($n)));
        if ($new->isEmpty()) {
            throw ValidationException::withMessages(['names' => 'This class already has '.($names->count() === 1 ? 'an arm with that name' : 'those arms').'.']);
        }

        foreach ($new as $name) {
            Section::create(['class_id' => $data['class_id'], 'name' => $name, 'department' => ($data['department'] ?? null) ?: null, 'capacity' => $data['capacity'] ?? 0]);
        }
        $skipped = $names->count() - $new->count();

        return back()->with('success', $new->count().' arm'.($new->count() === 1 ? '' : 's').' added.'.($skipped ? " {$skipped} already existed." : ''));
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sections', 'name')->where('class_id', $section->class_id)->whereNull('deleted_at')->ignore($section->id)],
            'department' => 'nullable|string|max:60',
            'capacity' => 'nullable|integer|min:0|max:1000',
        ], ['name.unique' => 'This class already has an arm with that name.']);

        $section->update(['name' => trim($data['name']), 'department' => ($data['department'] ?? null) ?: null, 'capacity' => $data['capacity'] ?? 0]);

        return back()->with('success', 'Arm saved.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        \App\Models\Student::where('section_id', $section->id)->update(['section_id' => null]);
        $section->delete();

        return back()->with('success', 'Arm deleted. Its students stay in the class without an arm.');
    }

    /** The usual departments plus any the school has typed on an arm */
    public static function departments(int $schoolId): array
    {
        $used = Section::where('school_id', $schoolId)->whereNotNull('department')->distinct()->pluck('department');

        return collect(self::DEPARTMENTS)->merge($used)->unique(fn ($d) => mb_strtolower($d))->values()->all();
    }
}
