<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardCommentBank;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
use App\Models\ReportCardSigner;
use App\Models\ResultSheet;
use App\Models\TermResultSummary;
use App\Services\ReportCardCommentService;
use App\Services\ReportCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Report cards for one class and term: the comments page (one column per signer the class's
 * design has: Class Teacher, Head Teacher ...), and the term and full-year PDFs (one student,
 * or the whole class in one file). Cards for results that are not published yet say "Preview".
 */
class ReportCardController extends Controller
{
    public function __construct(private ReportCardService $cards) {}

    public function index(Request $request, ResultSheet $sheet): InertiaResponse
    {
        $sheet->load(['term.academicYear:id,name', 'schoolClass:id,name']);
        $user = $request->user();
        $design = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id)->load('signers');
        $signers = $design->signers->where('has_comment', true)->values();
        $remarks = ReportCardRemark::where('result_sheet_id', $sheet->id)->get()->groupBy('student_id');

        return Inertia::render('SchoolAdmin/Results/ReportCards', [
            'sheet' => [
                'id' => $sheet->id,
                'status' => $sheet->status,
                'term' => trim(($sheet->term?->academicYear?->name ?? '').' · '.$sheet->term?->name, ' ·'),
                'class_name' => $sheet->schoolClass?->name,
            ],
            'design' => ['id' => $design->id, 'name' => $design->name],
            'signers' => $signers->map(fn (ReportCardSigner $s) => [
                'id' => $s->id,
                'label' => $s->label,
                'writer_permission' => $s->writer_permission,
                'can_write' => $sheet->status !== 'locked' && $s->canWrite($user),
            ]),
            'students' => TermResultSummary::where('result_sheet_id', $sheet->id)->with('student:id,first_name,last_name,admission_no,gender')->get()
                ->filter(fn ($s) => $s->student)
                ->sortBy(fn ($s) => $s->student->full_name)
                ->map(fn (TermResultSummary $s) => [
                    'id' => $s->student_id,
                    'name' => $s->student->full_name,
                    'admission_no' => $s->student->admission_no,
                    'first_name' => $s->student->first_name,
                    'gender' => $s->student->gender,
                    'average' => $s->average,
                    'position' => $s->position,
                    'class_size' => $s->class_size,
                    'remarks' => (object) ($remarks[$s->student_id] ?? collect())->pluck('comment', 'report_card_signer_id')->all(),
                ])->values(),
            'canDesign' => $user->can('settings.edit'),
            // Saved comments for the boxes this person may write, to pick from per student
            'bank' => ReportCardCommentBank::whereIn('writer_permission', $signers->filter(fn ($s) => $s->canWrite($user))->pluck('writer_permission')->unique())
                ->orderByDesc('min_average')->orderBy('id')->get(['id', 'writer_permission', 'min_average', 'max_average', 'comment']),
        ]);
    }

    /** Fill one signer's comments for the whole class from the comment bank (or one same comment) */
    public function fillComments(Request $request, ResultSheet $sheet, ReportCardCommentService $comments): RedirectResponse
    {
        if ($sheet->status === 'locked') {
            throw ValidationException::withMessages(['comments' => 'These results are locked, so comments can no longer be changed.']);
        }

        $data = $request->validate([
            'signer_id' => 'required|integer',
            'overwrite' => 'boolean',
            'text' => 'nullable|string|max:600',
        ]);

        $design = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id);
        $signer = ReportCardSigner::where('report_card_design_id', $design->id)->findOrFail($data['signer_id']);
        abort_unless($signer->canWrite($request->user()), 403);

        $text = trim((string) ($data['text'] ?? ''));
        $written = $comments->fill($sheet, $signer, $request->user(), $request->boolean('overwrite'), $text !== '' ? $text : null);

        return $written
            ? back()->with('success', "{$written} {$signer->label} ".str('comment')->plural($written).' filled in. Check them and change any you want.')
            : back()->with('info', 'No comments were filled in. Every student may already have one, or no saved comment fits their averages. Add comments on the Comment Bank page.');
    }

    /** One signer's comments for the class. Who may write is set on the signer (teachers or heads). */
    public function saveComments(Request $request, ResultSheet $sheet): RedirectResponse
    {
        if ($sheet->status === 'locked') {
            throw ValidationException::withMessages(['comments' => 'These results are locked, so comments can no longer be changed.']);
        }

        $data = $request->validate([
            'signer_id' => 'required|integer',
            'comments' => 'present|array|max:500',
            'comments.*.student_id' => 'required|integer',
            'comments.*.comment' => 'nullable|string|max:600',
        ]);

        $design = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id);
        $signer = ReportCardSigner::where('report_card_design_id', $design->id)->findOrFail($data['signer_id']);
        abort_unless($signer->canWrite($request->user()), 403);

        $onSheet = TermResultSummary::where('result_sheet_id', $sheet->id)->pluck('student_id')->flip();
        $existing = ReportCardRemark::where('result_sheet_id', $sheet->id)->where('report_card_signer_id', $signer->id)->get()->keyBy('student_id');

        foreach ($data['comments'] as $row) {
            if (! $onSheet->has($row['student_id'])) {
                continue;
            }
            $text = trim((string) ($row['comment'] ?? ''));
            $current = $existing[$row['student_id']] ?? null;

            if ($text === '') {
                $current?->delete();
            } elseif (! $current || $current->comment !== $text) {
                ReportCardRemark::updateOrCreate(
                    ['result_sheet_id' => $sheet->id, 'student_id' => $row['student_id'], 'report_card_signer_id' => $signer->id],
                    ['school_id' => $sheet->school_id, 'comment' => $text, 'written_by' => $request->user()->id],
                );
            }
        }

        return back()->with('success', "{$signer->label}'s comments saved.");
    }

    /** Term report card PDF: ?student=ID for one student, else the whole class */
    public function term(Request $request, ResultSheet $sheet): Response
    {
        $student = $request->integer('student') ?: null;
        $cards = $this->cards->termCards($sheet, $student ? [$student] : null);
        abort_if(! $cards, 404, 'There are no results to print for this class and term yet.');

        $sheet->loadMissing(['term', 'schoolClass']);
        $name = $student ? $cards[0]['student']['name'] : $sheet->schoolClass?->name;

        return self::pdfResponse('report-cards.term', $cards, "Report card {$name} {$sheet->term?->name}");
    }

    /** Full-year report card PDF for the sheet's class and school year */
    public function session(Request $request, ResultSheet $sheet): Response
    {
        $sheet->loadMissing(['term.academicYear', 'schoolClass']);
        abort_if(! $sheet->term?->academicYear, 404);

        $student = $request->integer('student') ?: null;
        $cards = $this->cards->sessionCards($sheet->term->academicYear, $sheet->class_id, $student ? [$student] : null);
        abort_if(! $cards, 404, 'There are no results to print for this class and year yet.');
        $name = $student ? $cards[0]['student']['name'] : $sheet->schoolClass?->name;

        return self::pdfResponse('report-cards.session', $cards, "Full year report {$name} {$sheet->term->academicYear->name}");
    }

    /** The PDF itself, on the paper size the design asks for */
    public static function pdfBytes(string $view, array $cards): string
    {
        return Pdf::loadView($view, ['cards' => $cards])
            ->setPaper($cards[0]['design']['paper'] ?? 'a4', 'portrait')
            ->output();
    }

    public static function pdfResponse(string $view, array $cards, string $title): Response
    {
        return response(self::pdfBytes($view, $cards), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::slug($title).'.pdf"',
        ]);
    }
}
