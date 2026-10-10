<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardComment;
use App\Models\ResultSheet;
use App\Models\TermResultSummary;
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
 * Report cards for one class and term: the comments page, and the term and full-year PDFs
 * (one student, or the whole class in one file). Staff can print them at any step;
 * cards for results that are not published yet carry a "preview" mark.
 */
class ReportCardController extends Controller
{
    public function __construct(private ReportCardService $cards) {}

    public function index(Request $request, ResultSheet $sheet): InertiaResponse
    {
        $sheet->load(['term.academicYear:id,name', 'schoolClass:id,name']);
        $user = $request->user();
        $comments = ReportCardComment::where('result_sheet_id', $sheet->id)->get()->keyBy('student_id');

        return Inertia::render('SchoolAdmin/Results/ReportCards', [
            'sheet' => [
                'id' => $sheet->id,
                'status' => $sheet->status,
                'term' => trim(($sheet->term?->academicYear?->name ?? '').' · '.$sheet->term?->name, ' ·'),
                'class_name' => $sheet->schoolClass?->name,
            ],
            'students' => TermResultSummary::where('result_sheet_id', $sheet->id)->with('student:id,first_name,last_name,admission_no')->get()
                ->filter(fn ($s) => $s->student)
                ->sortBy(fn ($s) => $s->student->full_name)
                ->map(fn (TermResultSummary $s) => [
                    'id' => $s->student_id,
                    'name' => $s->student->full_name,
                    'admission_no' => $s->student->admission_no,
                    'average' => $s->average,
                    'position' => $s->position,
                    'class_size' => $s->class_size,
                    'teacher_comment' => $comments[$s->student_id]?->teacher_comment ?? '',
                    'principal_comment' => $comments[$s->student_id]?->principal_comment ?? '',
                ])->values(),
            'can' => [
                'teacher' => $sheet->status !== 'locked' && $user->can('marks.entry'),
                'principal' => $sheet->status !== 'locked' && $user->can('results.publish'),
            ],
        ]);
    }

    /** The class teacher's comments (needs marks.entry) */
    public function saveTeacherComments(Request $request, ResultSheet $sheet): RedirectResponse
    {
        return $this->saveComments($request, $sheet, 'teacher');
    }

    /** The principal's comments (needs results.publish) */
    public function savePrincipalComments(Request $request, ResultSheet $sheet): RedirectResponse
    {
        return $this->saveComments($request, $sheet, 'principal');
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

    private function saveComments(Request $request, ResultSheet $sheet, string $who): RedirectResponse
    {
        if ($sheet->status === 'locked') {
            throw ValidationException::withMessages(['comments' => 'These results are locked, so comments can no longer be changed.']);
        }

        $data = $request->validate([
            'comments' => 'present|array|max:500',
            'comments.*.student_id' => 'required|integer',
            'comments.*.comment' => 'nullable|string|max:600',
        ]);

        $onSheet = TermResultSummary::where('result_sheet_id', $sheet->id)->pluck('student_id')->flip();
        $column = "{$who}_comment";

        foreach ($data['comments'] as $row) {
            if (! $onSheet->has($row['student_id'])) {
                continue;
            }
            $text = trim((string) ($row['comment'] ?? '')) ?: null;
            $existing = ReportCardComment::where('result_sheet_id', $sheet->id)->where('student_id', $row['student_id'])->first();
            if (! $existing && $text === null) {
                continue;
            }
            if ($existing && $existing->{$column} === $text) {
                continue;
            }

            ReportCardComment::updateOrCreate(
                ['result_sheet_id' => $sheet->id, 'student_id' => $row['student_id']],
                ['school_id' => $sheet->school_id, $column => $text, "{$who}_by" => $request->user()->id],
            );
        }

        return back()->with('success', $who === 'teacher' ? "Class teacher's comments saved." : "Principal's comments saved.");
    }

    public static function pdfResponse(string $view, array $cards, string $title): Response
    {
        return Pdf::loadView($view, ['cards' => $cards])->setPaper('a4', 'portrait')
            ->stream(Str::slug($title).'.pdf');
    }
}
