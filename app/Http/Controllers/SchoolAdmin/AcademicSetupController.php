<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssessmentScheme;
use App\Models\GradeScale;
use App\Models\GradingScheme;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Support\SchoolDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * School years and terms, score setups (CA1, CA2, Exam ...) and grade scales.
 * Everything here is chosen by the school, starting from the defaults in SchoolDefaults.
 */
class AcademicSetupController extends Controller
{
    // ───────────────────────── school years & terms ─────────────────────────

    public function terms(): Response
    {
        return Inertia::render('SchoolAdmin/Academics/Terms', [
            'years' => AcademicYear::with('terms')->orderByDesc('start_date')->get()
                ->map(fn (AcademicYear $y) => [
                    'id' => $y->id, 'name' => $y->name, 'is_current' => $y->is_current,
                    'start_date' => $y->start_date?->toDateString(), 'end_date' => $y->end_date?->toDateString(),
                    'terms' => $y->terms->map->only('id', 'name', 'sequence', 'start_date', 'end_date', 'is_current')->values(),
                ]),
            'canEdit' => (bool) auth()->user()?->can('exams.edit'),
        ]);
    }

    public function storeYear(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:20', Rule::unique('academic_years')->where('school_id', $sid)->whereNull('deleted_at')],
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'make_current' => 'boolean',
        ]);

        DB::transaction(function () use ($data, $sid) {
            // Creating the year also creates its terms (see AcademicYear::booted)
            $year = AcademicYear::create([
                'school_id' => $sid, 'name' => $data['name'],
                'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'is_current' => false,
            ]);

            if ($data['make_current'] ?? false) {
                $year->makeCurrent();
                $year->terms()->first()?->makeCurrent();
            }
        });

        return back()->with('success', 'School year added with its terms.');
    }

    public function updateTerm(Request $request, Term $term): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:50',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $term->update($data);

        return back()->with('success', 'Term updated.');
    }

    public function makeTermCurrent(Term $term): RedirectResponse
    {
        DB::transaction(function () use ($term) {
            $term->makeCurrent();
            $term->academicYear?->makeCurrent();
        });

        return back()->with('success', "{$term->name} is now the current term.");
    }

    // ───────────────────────── score setups & grade scales ─────────────────────────

    public function assessment(): Response
    {
        return Inertia::render('SchoolAdmin/Academics/Assessment', [
            'assessmentSchemes' => AssessmentScheme::with('components')->orderByDesc('is_default')->orderBy('name')->get()
                ->map(fn (AssessmentScheme $s) => [
                    'id' => $s->id, 'name' => $s->name, 'is_default' => $s->is_default,
                    'components' => $s->components->map->only('id', 'name', 'short_name', 'max_score')->values(),
                ]),
            'gradingSchemes' => GradingScheme::with('bands')->orderByDesc('is_default')->orderBy('name')->get()
                ->map(fn (GradingScheme $s) => [
                    'id' => $s->id, 'name' => $s->name, 'is_default' => $s->is_default,
                    'bands' => $s->bands->map(fn (GradeScale $b) => [
                        'id' => $b->id, 'grade' => $b->grade, 'remarks' => $b->remarks,
                        'min_marks' => (float) $b->min_marks, 'max_marks' => (float) $b->max_marks, 'gpa' => (float) $b->gpa,
                    ])->values(),
                ]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name', 'assessment_scheme_id', 'grading_scheme_id']),
            'subjects' => Subject::with('schoolClass:id,name')->orderBy('name')->get(['id', 'name', 'class_id', 'assessment_scheme_id'])
                ->map(fn (Subject $s) => [
                    'id' => $s->id, 'name' => $s->name, 'class_name' => $s->schoolClass?->name,
                    'assessment_scheme_id' => $s->assessment_scheme_id,
                ]),
            'presets' => [
                'assessment' => collect(SchoolDefaults::ASSESSMENT_PRESETS)->map(fn ($p, $key) => [
                    'key' => $key, 'name' => $p['name'],
                    'components' => array_map(fn ($c) => ['name' => $c[0], 'short_name' => $c[1], 'max_score' => $c[2]], $p['components']),
                ])->values(),
                'grading' => collect(SchoolDefaults::GRADING_PRESETS)->map(fn ($p, $key) => [
                    'key' => $key, 'name' => $p['name'],
                    'bands' => array_map(fn ($b) => ['grade' => $b[0], 'min_marks' => $b[1], 'max_marks' => $b[2], 'remarks' => $b[3], 'gpa' => $b[4]], $p['bands']),
                ])->values(),
            ],
            'canEdit' => (bool) auth()->user()?->can('exams.edit'),
        ]);
    }

    public function storeAssessmentScheme(Request $request): RedirectResponse
    {
        return $this->saveAssessmentScheme($request, new AssessmentScheme(['school_id' => $this->getSchoolId()]));
    }

    public function updateAssessmentScheme(Request $request, AssessmentScheme $scheme): RedirectResponse
    {
        return $this->saveAssessmentScheme($request, $scheme);
    }

    private function saveAssessmentScheme(Request $request, AssessmentScheme $scheme): RedirectResponse
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:100',
            'is_default'              => 'boolean',
            'components'              => 'required|array|min:1|max:10',
            'components.*.id'         => 'nullable|integer',
            'components.*.name'       => 'required|string|max:50',
            'components.*.short_name' => 'required|string|max:10|distinct:ignore_case',
            'components.*.max_score'  => 'required|numeric|min:1|max:100',
        ], [
            'components.*.short_name.distinct' => 'Each score part needs a different short name.',
        ]);

        $total = round(array_sum(array_column($data['components'], 'max_score')), 2);
        if (abs($total - 100) > 0.001) {
            throw ValidationException::withMessages([
                'components' => "The score parts must add up to 100. They add up to {$total} now.",
            ]);
        }

        DB::transaction(function () use ($data, $scheme) {
            $scheme->fill(['name' => $data['name']])->save();

            // Keep the ids of parts that stay, so scores entered against them later stay linked
            $keep = [];
            foreach (array_values($data['components']) as $i => $c) {
                $part = $scheme->components()->find($c['id'] ?? 0) ?? $scheme->components()->make(['school_id' => $scheme->school_id]);
                $part->fill(['name' => $c['name'], 'short_name' => $c['short_name'], 'max_score' => $c['max_score'], 'sort_order' => $i + 1])->save();
                $keep[] = $part->id;
            }
            $scheme->components()->whereNotIn('id', $keep)->delete();

            if (($data['is_default'] ?? false) || ! AssessmentScheme::where('school_id', $scheme->school_id)->where('is_default', true)->exists()) {
                $this->makeDefault(AssessmentScheme::class, $scheme);
            }

            activity()->performedOn($scheme)
                ->withProperties(['components' => collect($data['components'])->map(fn ($c) => "{$c['short_name']} {$c['max_score']}")->all()])
                ->log('Score setup saved');
        });

        return back()->with('success', 'Score setup saved.');
    }

    public function destroyAssessmentScheme(AssessmentScheme $scheme): RedirectResponse
    {
        if ($scheme->is_default) {
            return back()->with('error', 'Choose another default score setup before deleting this one.');
        }

        DB::transaction(function () use ($scheme) {
            // Classes and subjects that used it go back to the school default
            SchoolClass::where('assessment_scheme_id', $scheme->id)->update(['assessment_scheme_id' => null]);
            Subject::where('assessment_scheme_id', $scheme->id)->update(['assessment_scheme_id' => null]);
            $scheme->delete();
            activity()->performedOn($scheme)->log('Score setup deleted');
        });

        return back()->with('success', 'Score setup deleted.');
    }

    public function storeGradingScheme(Request $request): RedirectResponse
    {
        return $this->saveGradingScheme($request, new GradingScheme(['school_id' => $this->getSchoolId()]));
    }

    public function updateGradingScheme(Request $request, GradingScheme $scheme): RedirectResponse
    {
        return $this->saveGradingScheme($request, $scheme);
    }

    private function saveGradingScheme(Request $request, GradingScheme $scheme): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'is_default'        => 'boolean',
            'bands'             => 'required|array|min:2|max:20',
            'bands.*.id'        => 'nullable|integer',
            'bands.*.grade'     => 'required|string|max:10|distinct:ignore_case',
            'bands.*.min_marks' => 'required|numeric|min:0|max:100|distinct',
            'bands.*.max_marks' => 'required|numeric|min:0|max:100|gte:bands.*.min_marks',
            'bands.*.remarks'   => 'nullable|string|max:50',
            'bands.*.gpa'       => 'nullable|numeric|min:0|max:5',
        ], [
            'bands.*.grade.distinct'     => 'Each grade must have a different name.',
            'bands.*.min_marks.distinct' => 'Two grades cannot start at the same score.',
            'bands.*.max_marks.gte'      => 'A grade cannot end below the score it starts at.',
        ]);

        // The lowest grade must start at 0, so every score gets a grade
        if (min(array_map('floatval', array_column($data['bands'], 'min_marks'))) > 0) {
            throw ValidationException::withMessages([
                'bands' => 'The lowest grade must start at 0, so every score gets a grade.',
            ]);
        }

        DB::transaction(function () use ($data, $scheme) {
            $scheme->fill(['name' => $data['name']])->save();

            $keep = [];
            $bands = collect($data['bands'])->sortByDesc(fn ($b) => (float) $b['min_marks'])->values();
            foreach ($bands as $i => $b) {
                $band = $scheme->bands()->find($b['id'] ?? 0) ?? $scheme->bands()->make(['school_id' => $scheme->school_id]);
                $band->fill([
                    'grade' => $b['grade'], 'min_marks' => $b['min_marks'], 'max_marks' => $b['max_marks'],
                    'remarks' => $b['remarks'] ?? null, 'gpa' => $b['gpa'] ?? 0, 'sort_order' => $i + 1,
                ])->save();
                $keep[] = $band->id;
            }
            $scheme->bands()->whereNotIn('id', $keep)->delete();

            if (($data['is_default'] ?? false) || ! GradingScheme::where('school_id', $scheme->school_id)->where('is_default', true)->exists()) {
                $this->makeDefault(GradingScheme::class, $scheme);
            }

            activity()->performedOn($scheme)
                ->withProperties(['bands' => $bands->map(fn ($b) => "{$b['grade']} {$b['min_marks']}-{$b['max_marks']}")->all()])
                ->log('Grade scale saved');
        });

        return back()->with('success', 'Grade scale saved.');
    }

    public function destroyGradingScheme(GradingScheme $scheme): RedirectResponse
    {
        if ($scheme->is_default) {
            return back()->with('error', 'Choose another default grade scale before deleting this one.');
        }

        DB::transaction(function () use ($scheme) {
            SchoolClass::where('grading_scheme_id', $scheme->id)->update(['grading_scheme_id' => null]);
            $scheme->delete();
            activity()->performedOn($scheme)->log('Grade scale deleted');
        });

        return back()->with('success', 'Grade scale deleted.');
    }

    /** Pick a score setup and grade scale for one class. Empty means "school default". */
    public function assignClass(Request $request, SchoolClass $class): RedirectResponse
    {
        $sid = $class->school_id;
        $data = $request->validate([
            'assessment_scheme_id' => ['nullable', Rule::exists('assessment_schemes', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'grading_scheme_id'    => ['nullable', Rule::exists('grading_schemes', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
        ]);

        $class->update($data);

        return back()->with('success', "{$class->name} updated.");
    }

    /** Give one subject its own score setup, or send it back to its class's. */
    public function assignSubject(Request $request, Subject $subject): RedirectResponse
    {
        $data = $request->validate([
            'assessment_scheme_id' => ['nullable', Rule::exists('assessment_schemes', 'id')->where('school_id', $subject->school_id)->whereNull('deleted_at')],
        ]);

        $subject->update($data);

        return back()->with('success', "{$subject->name} updated.");
    }

    /** @param class-string<AssessmentScheme|GradingScheme> $model */
    private function makeDefault(string $model, AssessmentScheme|GradingScheme $scheme): void
    {
        $model::where('school_id', $scheme->school_id)->where('id', '!=', $scheme->id)->update(['is_default' => false]);
        $scheme->forceFill(['is_default' => true])->save();
    }
}
