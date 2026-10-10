<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SchoolAdmin\ReportCardController;
use App\Models\Guardian;
use App\Models\ResultSheet;
use App\Models\Student;
use App\Models\TermResultSummary;
use App\Services\ReportCardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Report card downloads for students and parents. Only published or locked results,
 * and only for the student themselves or a parent's own child.
 */
class ReportCardPortalController extends Controller
{
    public function __construct(private ReportCardService $cards) {}

    public function studentTerm(Request $request, int $sheet): Response
    {
        return $this->term($this->ownStudent($request), $sheet);
    }

    public function studentSession(Request $request, int $sheet): Response
    {
        return $this->session($this->ownStudent($request), $sheet);
    }

    public function parentTerm(Request $request, int $student, int $sheet): Response
    {
        return $this->term($this->child($request, $student), $sheet);
    }

    public function parentSession(Request $request, int $student, int $sheet): Response
    {
        return $this->session($this->child($request, $student), $sheet);
    }

    private function term(Student $student, int $sheetId): Response
    {
        $sheet = $this->releasedSheet($student, $sheetId);
        $cards = $this->cards->termCards($sheet, [$student->id]);
        abort_if(! $cards, 404);

        return ReportCardController::pdfResponse('report-cards.term', $cards, "Report card {$student->full_name} {$sheet->term?->name}");
    }

    /** The full-year card for the year of a released term, counting released terms only */
    private function session(Student $student, int $sheetId): Response
    {
        $sheet = $this->releasedSheet($student, $sheetId);
        $year = $sheet->term?->academicYear;
        abort_if(! $year, 404);

        $cards = $this->cards->sessionCards($year, $sheet->class_id, [$student->id], releasedOnly: true);
        abort_if(! $cards, 404);

        return ReportCardController::pdfResponse('report-cards.session', $cards, "Full year report {$student->full_name} {$year->name}");
    }

    /** A sheet the student has results on, and which has been published */
    private function releasedSheet(Student $student, int $sheetId): ResultSheet
    {
        $sheet = ResultSheet::where('school_id', $student->school_id)
            ->whereIn('status', ReportCardService::RELEASED)
            ->with('term.academicYear')
            ->findOrFail($sheetId);

        abort_unless(TermResultSummary::where('result_sheet_id', $sheet->id)->where('student_id', $student->id)->exists(), 404);

        return $sheet;
    }

    private function ownStudent(Request $request): Student
    {
        $user = $request->user();

        return Student::where('school_id', $user->school_id)->where('user_id', $user->id)->firstOrFail();
    }

    private function child(Request $request, int $studentId): Student
    {
        $user = $request->user();
        $guardian = Guardian::where('school_id', $user->school_id)->where('user_id', $user->id)->firstOrFail();

        return Student::where('school_id', $user->school_id)->where('guardian_id', $guardian->id)->findOrFail($studentId);
    }
}
