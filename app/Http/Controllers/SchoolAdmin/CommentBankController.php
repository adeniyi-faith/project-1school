<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardCommentBank;
use App\Models\ReportCardDesign;
use App\Models\ReportCardSigner;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\ReportCardCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The comment bank: saved comments for teachers' and heads' comment boxes, each for an
 * average range, and a form to fill one signer's comments (e.g. the Principal's) on many
 * classes at once, from the bank or with one same comment.
 */
class CommentBankController extends Controller
{
    /** Ready-made comments a school can start from and then change */
    public const STARTERS = [
        'marks.entry' => [
            [70, 100, '{name} is an excellent pupil. {he_she} works hard and takes part well in class. Keep it up.'],
            [70, 100, 'An outstanding result. {name} is focused, neat and very well behaved.'],
            [60, 69.99, '{name} did very well this term. With a little more effort {he_she} can reach the top.'],
            [50, 59.99, 'A good result. {name} should pay more attention in class and revise daily.'],
            [40, 49.99, 'A fair result. {name} needs to put in more effort and ask questions when {he_she} is unsure.'],
            [0, 39.99, '{name} must work much harder next term. Regular revision and extra lessons will help {him_her}.'],
        ],
        'results.publish' => [
            [70, 100, 'An excellent result. Keep it up.'],
            [60, 69.99, 'A very good result. Aim higher next term.'],
            [50, 59.99, 'A good result. There is room for improvement.'],
            [40, 49.99, 'A fair result. Work harder next term.'],
            [0, 39.99, 'A poor result. {name} must work much harder next term.'],
        ],
    ];

    public function __construct(private ReportCardCommentService $comments) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $writers = collect(ReportCardSigner::WRITERS)->filter(fn ($l, $perm) => $user->can($perm));

        $terms = Term::with('academicYear:id,name,start_date')->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $b->academicYear?->start_date, (string) $a->academicYear?->start_date), ['sequence', 'asc']])
            ->values();
        $term = $terms->firstWhere('id', (int) $request->term_id) ?? $terms->firstWhere('is_current', true) ?? $terms->first();

        $sheets = collect();
        $labels = collect();
        if ($term) {
            $classes = SchoolClass::orderBy('numeric_name')->orderBy('id')->get(['id', 'name', 'report_card_design_id'])->keyBy('id');
            $designs = ReportCardDesign::with('signers')->get()->keyBy('id');
            $default = ReportCardDesign::forClass($term->school_id, null)->load('signers');
            $sheets = ResultSheet::where('term_id', $term->id)->withCount('summaries')->get()
                ->filter(fn (ResultSheet $s) => $classes->has($s->class_id))
                ->sortBy(fn (ResultSheet $s) => $classes->keys()->search($s->class_id))
                ->map(function (ResultSheet $s) use ($classes, $designs, $default, $user) {
                    $design = $designs[$classes[$s->class_id]->report_card_design_id] ?? $default;

                    return [
                        'id' => $s->id,
                        'class_name' => $classes[$s->class_id]->name,
                        'status' => $s->status,
                        'students' => $s->summaries_count,
                        'labels' => $design->signers->filter(fn (ReportCardSigner $sg) => $sg->canWrite($user))->pluck('label')->values(),
                    ];
                })->values();
            $labels = $sheets->pluck('labels')->flatten()->unique(fn ($l) => mb_strtolower($l))->values();
        }

        return Inertia::render('SchoolAdmin/Results/CommentBank', [
            'writers' => $writers->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'entries' => ReportCardCommentBank::whereIn('writer_permission', $writers->keys())
                ->orderBy('writer_permission')->orderByDesc('min_average')->orderBy('id')
                ->get(['id', 'writer_permission', 'min_average', 'max_average', 'comment']),
            'terms' => $terms->map(fn (Term $t) => [
                'id' => $t->id, 'label' => trim(($t->academicYear?->name ?? '').' · '.$t->name, ' ·'), 'is_current' => $t->is_current,
            ]),
            'termId' => $term?->id,
            'sheets' => $sheets,
            'signerLabels' => $labels,
            'blanks' => ReportCardCommentBank::BLANKS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        ReportCardCommentBank::create($data + ['school_id' => $request->user()->school_id]);

        return back()->with('success', 'Comment saved to the bank.');
    }

    public function update(Request $request, ReportCardCommentBank $entry): RedirectResponse
    {
        abort_unless($request->user()->can($entry->writer_permission), 403);
        $entry->update($this->validated($request));

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Request $request, ReportCardCommentBank $entry): RedirectResponse
    {
        abort_unless($request->user()->can($entry->writer_permission), 403);
        $entry->delete();

        return back()->with('success', 'Comment removed from the bank.');
    }

    /** Add the ready-made comments for teachers or heads */
    public function starters(Request $request): RedirectResponse
    {
        $data = $request->validate(['writer_permission' => ['required', Rule::in(array_keys(self::STARTERS))]]);
        abort_unless($request->user()->can($data['writer_permission']), 403);

        foreach (self::STARTERS[$data['writer_permission']] as [$min, $max, $text]) {
            ReportCardCommentBank::firstOrCreate(
                ['school_id' => $request->user()->school_id, 'writer_permission' => $data['writer_permission'], 'comment' => $text],
                ['min_average' => $min, 'max_average' => $max],
            );
        }

        return back()->with('success', 'Ready-made comments added. Change them to suit your school.');
    }

    /** Fill one signer's comments (matched by title) on many classes of one term */
    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->where('school_id', $request->user()->school_id)],
            'sheet_ids' => 'required|array|min:1',
            'sheet_ids.*' => 'integer',
            'signer_label' => 'required|string|max:60',
            'mode' => ['required', Rule::in(['bank', 'same'])],
            'text' => 'required_if:mode,same|nullable|string|max:600',
            'overwrite' => 'boolean',
        ], [
            'sheet_ids.required' => 'Choose at least one class.',
            'text.required_if' => 'Write the comment to use.',
        ]);

        $sheets = ResultSheet::where('term_id', $data['term_id'])->whereIn('id', $data['sheet_ids'])->with('schoolClass:id,name')->get();
        if ($sheets->isEmpty()) {
            throw ValidationException::withMessages(['sheet_ids' => 'Choose at least one class.']);
        }

        [$classes, $written, $skipped] = $this->comments->fillMany(
            $sheets, $data['signer_label'], $request->user(), $request->boolean('overwrite'),
            $data['mode'] === 'same' ? $data['text'] : null,
        );

        $message = $written
            ? "{$written} ".str('comment')->plural($written)." written across {$classes} ".str('class')->plural($classes).'.'
            : 'No comments were written. Students may already have comments (tick "Replace comments already written"), or no saved comment fits their averages.';
        $redirect = back()->with($written ? 'success' : 'info', $message);

        return $skipped ? $redirect->with('error', 'Skipped: '.implode('; ', $skipped).'.') : $redirect;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'writer_permission' => ['required', Rule::in(array_keys(ReportCardSigner::WRITERS))],
            'min_average' => 'required|numeric|min:0|max:100',
            'max_average' => 'required|numeric|min:0|max:100|gte:min_average',
            'comment' => 'required|string|max:600',
        ], ['max_average.gte' => 'The top of the range must not be below the bottom.']);
        abort_unless($request->user()->can($data['writer_permission']), 403);

        return $data;
    }
}
