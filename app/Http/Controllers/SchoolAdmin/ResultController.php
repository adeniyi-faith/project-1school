<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentScheme;
use App\Models\BehaviourRating;
use App\Models\BehaviourTrait;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectScore;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Services\TermResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Term results: scores entered per score part, behaviour ratings, the stored
 * results with positions, and the draft → submitted → approved → published → locked steps.
 */
class ResultController extends Controller
{
    public function __construct(private TermResultService $results) {}

    /** Every class's results for one term, with where each is in the approval steps. */
    public function index(Request $request): Response
    {
        $terms = Term::with('academicYear:id,name,start_date')->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $b->academicYear?->start_date, (string) $a->academicYear?->start_date), ['sequence', 'asc']])
            ->values();
        $term = $terms->firstWhere('id', (int) $request->term_id) ?? $terms->firstWhere('is_current', true) ?? $terms->first();

        $sheets = collect();
        if ($term) {
            $sid = $term->school_id;
            // Make sure every class has a sheet for this term
            $classes = SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']);
            foreach ($classes as $class) {
                ResultSheet::firstOrCreate(['term_id' => $term->id, 'class_id' => $class->id], ['school_id' => $sid]);
            }

            $students = Student::where('school_id', $sid)->where('status', 'active')->groupBy('class_id')
                ->select('class_id', DB::raw('count(*) as n'))->pluck('n', 'class_id');

            $sheets = ResultSheet::where('term_id', $term->id)->withCount('summaries')->get()->keyBy('class_id');
            $sheets = $classes->map(fn (SchoolClass $c) => [
                'id' => $sheets[$c->id]->id,
                'class_name' => $c->name,
                'status' => $sheets[$c->id]->status,
                'students' => (int) ($students[$c->id] ?? 0),
                'with_results' => $sheets[$c->id]->summaries_count,
                'class_average' => $sheets[$c->id]->class_average,
            ]);
        }

        return Inertia::render('SchoolAdmin/Results/Index', [
            'terms' => $terms->map(fn (Term $t) => [
                'id' => $t->id, 'label' => trim(($t->academicYear?->name ?? '') . ' · ' . $t->name, ' ·'), 'is_current' => $t->is_current,
            ]),
            'termId' => $term?->id,
            'sheets' => $sheets->values(),
        ]);
    }

    public function show(Request $request, ResultSheet $sheet): Response
    {
        $sheet->load(['term.academicYear:id,name', 'schoolClass:id,name']);
        $user = $request->user();

        $subjects = Subject::with('schoolClass:id,assessment_scheme_id')->where('class_id', $sheet->class_id)->orderBy('name')->get();
        $subject = $subjects->firstWhere('id', (int) $request->subject_id) ?? $subjects->first();
        $scheme = $subject ? AssessmentScheme::forSubject($subject)?->load('components') : null;

        $students = $this->students($sheet);

        $scores = $subject
            ? SubjectScore::where('result_sheet_id', $sheet->id)->where('subject_id', $subject->id)->get()
                ->groupBy('student_id')->map(fn ($rows) => $rows->pluck('score', 'assessment_component_id'))
            : collect();

        return Inertia::render('SchoolAdmin/Results/Sheet', [
            'sheet' => [
                'id' => $sheet->id,
                'status' => $sheet->status,
                'version' => $sheet->version,
                'class_average' => $sheet->class_average,
                'computed_at' => $sheet->computed_at?->toIso8601String(),
                'term' => trim(($sheet->term?->academicYear?->name ?? '') . ' · ' . $sheet->term?->name, ' ·'),
                'class_name' => $sheet->schoolClass?->name,
                'steps' => collect(['submitted', 'approved', 'published', 'locked'])
                    ->mapWithKeys(fn ($s) => [$s => $sheet->{"{$s}_at"}?->toIso8601String()]),
            ],
            'actions' => $sheet->availableActions($user),
            'canEnter' => $sheet->isEditable() && $user->can('marks.entry'),
            'tab' => in_array($request->tab, ['scores', 'behaviour', 'results'], true) ? $request->tab : 'scores',
            'subjects' => $subjects->map->only('id', 'name'),
            'subjectId' => $subject?->id,
            'components' => $scheme?->components->map->only('id', 'name', 'short_name', 'max_score')->values() ?? [],
            'students' => $students,
            'scores' => $scores,
            'subjectResults' => $subject
                ? TermResult::where('result_sheet_id', $sheet->id)->where('subject_id', $subject->id)->get()
                    ->keyBy('student_id')->map->only('total', 'grade', 'remarks', 'subject_position')
                : (object) [],
            'summaries' => TermResultSummary::where('result_sheet_id', $sheet->id)->with('student:id,first_name,last_name,admission_no')
                ->orderBy('position')->get()
                ->map(fn (TermResultSummary $s) => [
                    'student_id' => $s->student_id, 'name' => $s->student?->full_name, 'admission_no' => $s->student?->admission_no,
                    'subjects_count' => $s->subjects_count, 'total_score' => $s->total_score, 'average' => $s->average,
                    'position' => $s->position, 'class_size' => $s->class_size,
                ]),
            'traits' => BehaviourTrait::orderBy('domain')->orderBy('sort_order')->get(['id', 'name', 'domain']),
            'ratings' => BehaviourRating::where('result_sheet_id', $sheet->id)->get()
                ->groupBy('student_id')->map(fn ($rows) => $rows->pluck('rating', 'behaviour_trait_id')),
            'ratingLabels' => BehaviourRating::LABELS,
        ]);
    }

    /** Save the part scores for one subject, then work the class's results out again. */
    public function saveScores(Request $request, ResultSheet $sheet): RedirectResponse
    {
        $this->ensureEditable($sheet);

        $data = $request->validate([
            'subject_id' => 'required|integer',
            'scores' => 'present|array',
            'scores.*.student_id' => 'required|integer',
            'scores.*.component_id' => 'required|integer',
            'scores.*.score' => 'nullable|numeric|min:0',
        ]);

        $subject = Subject::where('class_id', $sheet->class_id)->findOrFail($data['subject_id']);
        $components = AssessmentScheme::forSubject($subject)?->components->keyBy('id') ?? collect();
        $studentIds = $this->students($sheet)->pluck('id')->flip();

        $errors = [];
        foreach ($data['scores'] as $i => $row) {
            $part = $components->get($row['component_id']);
            if (! $part || ! $studentIds->has($row['student_id'])) {
                $errors["scores.$i.score"] = 'This score does not belong to this class and subject.';
            } elseif ($row['score'] !== null && (float) $row['score'] > $part->max_score) {
                $errors["scores.$i.score"] = "{$part->short_name} is out of {$part->max_score}. A score of {$row['score']} is too high.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($data, $sheet, $subject) {
            foreach ($data['scores'] as $row) {
                $key = [
                    'result_sheet_id' => $sheet->id, 'student_id' => $row['student_id'],
                    'subject_id' => $subject->id, 'assessment_component_id' => $row['component_id'],
                ];
                if ($row['score'] === null) {
                    SubjectScore::where($key)->first()?->delete();
                } else {
                    SubjectScore::updateOrCreate($key, ['school_id' => $sheet->school_id, 'score' => $row['score']]);
                }
            }
            $this->results->compute($sheet);
        });

        return back()->with('success', "{$subject->name} scores saved and results updated.");
    }

    /** Save behaviour and skills ratings (1 to 5). An empty rating removes it. */
    public function saveRatings(Request $request, ResultSheet $sheet): RedirectResponse
    {
        $this->ensureEditable($sheet);

        $data = $request->validate([
            'ratings' => 'present|array',
            'ratings.*.student_id' => 'required|integer',
            'ratings.*.trait_id' => 'required|integer',
            'ratings.*.rating' => 'nullable|integer|min:1|max:5',
        ]);

        $traitIds = BehaviourTrait::pluck('id')->flip();
        $studentIds = $this->students($sheet)->pluck('id')->flip();

        DB::transaction(function () use ($data, $sheet, $traitIds, $studentIds) {
            foreach ($data['ratings'] as $row) {
                if (! $traitIds->has($row['trait_id']) || ! $studentIds->has($row['student_id'])) {
                    continue;
                }
                $key = ['result_sheet_id' => $sheet->id, 'student_id' => $row['student_id'], 'behaviour_trait_id' => $row['trait_id']];
                if ($row['rating'] === null) {
                    BehaviourRating::where($key)->delete();
                } else {
                    BehaviourRating::updateOrCreate($key, ['school_id' => $sheet->school_id, 'rating' => $row['rating']]);
                }
            }
        });

        return back()->with('success', 'Ratings saved.');
    }

    /** Move the results a step: submit, approve, return, publish, unpublish, lock or unlock. */
    public function transition(Request $request, ResultSheet $sheet): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|string|in:' . implode(',', array_keys(ResultSheet::ACTIONS))]);

        if ($data['action'] === 'submit') {
            if (! $sheet->summaries()->exists()) {
                return back()->with('error', 'Enter some scores before submitting.');
            }
            $this->results->compute($sheet);
        }

        if (! $sheet->transition($data['action'], $request->user())) {
            return back()->with('error', 'You cannot do that from here.');
        }

        $messages = [
            'submit' => 'Results submitted for approval. Scores can no longer be changed.',
            'approve' => 'Results approved.',
            'return' => 'Results sent back for changes.',
            'publish' => 'Results published. Parents and students can now see them.',
            'unpublish' => 'Results hidden from parents and students again.',
            'lock' => 'Results locked.',
            'unlock' => 'Results unlocked.',
        ];

        return back()->with('success', $messages[$data['action']]);
    }

    private function ensureEditable(ResultSheet $sheet): void
    {
        if (! $sheet->isEditable()) {
            throw ValidationException::withMessages([
                'scores' => 'These results have been submitted, so they cannot be changed. Ask for them to be sent back first.',
            ]);
        }
    }

    /** Active students in the class, plus anyone who already has scores on this sheet. */
    private function students(ResultSheet $sheet)
    {
        $scored = SubjectScore::where('result_sheet_id', $sheet->id)->distinct()->pluck('student_id');

        return Student::where(fn ($q) => $q->where('class_id', $sheet->class_id)->where('status', 'active'))
            ->orWhereIn('id', $scored)
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no'])
            ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no]);
    }
}
