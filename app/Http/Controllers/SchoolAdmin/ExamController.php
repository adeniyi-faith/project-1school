<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(Request $request): Response
    {
        $exams = Exam::with('schoolClass:id,name')
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Exams/Index', [
            'exams' => [
                'data' => $exams->items(),
                'meta' => [
                    'total' => $exams->total(), 'per_page' => $exams->perPage(),
                    'current_page' => $exams->currentPage(), 'last_page' => $exams->lastPage(),
                    'from' => $exams->firstItem(), 'to' => $exams->lastItem(),
                ],
                'links' => ['prev' => $exams->previousPageUrl(), 'next' => $exams->nextPageUrl()],
            ],
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'filters' => $request->only('class_id', 'status'),
            'stats'   => [
                'total'     => Exam::count(),
                'draft'     => Exam::where('status', 'draft')->count(),
                'published' => Exam::where('status', 'published')->count(),
                'completed' => Exam::where('status', 'completed')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:unit_test,mid_term,final,custom',
            'class_id'    => 'required|exists:classes,id',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:draft,published,completed',
            'description' => 'nullable|string|max:500',
        ]);

        Exam::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Exam created.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:unit_test,mid_term,final,custom',
            'class_id'    => 'required|exists:classes,id',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:draft,published,completed',
            'description' => 'nullable|string|max:500',
        ]);

        $exam->update($data);
        return back()->with('success', 'Exam updated.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();
        return back()->with('success', 'Exam deleted.');
    }

    /**
     * Marks entry page for an exam — shows students × subjects grid.
     */
    public function marks(Request $request, Exam $exam): Response
    {
        $sectionId = $request->section_id;

        $subjects = Subject::where('class_id', $exam->class_id)->orderBy('name')->get();

        $students = Student::where('class_id', $exam->class_id)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->where('status', 'active')
            ->with('section:id,name')
            ->orderBy('roll_no')
            ->get(['id', 'first_name', 'last_name', 'roll_no', 'section_id']);

        // Existing marks keyed by student_id → subject_id
        $existingMarks = Mark::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($marks) => $marks->keyBy('subject_id'));

        return Inertia::render('SchoolAdmin/Exams/Marks', [
            'exam'          => $exam->load('schoolClass:id,name'),
            'subjects'      => $subjects,
            'students'      => $students,
            'existingMarks' => $existingMarks,
            'sections'      => Section::where('class_id', $exam->class_id)->orderBy('name')->get(['id', 'name']),
            'filters'       => ['section_id' => $sectionId],
        ]);
    }

    /**
     * Bulk save marks for an exam.
     */
    public function saveMarks(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'section_id'              => 'nullable|exists:sections,id',
            'marks'                   => 'required|array',
            'marks.*.student_id'      => 'required|exists:students,id',
            'marks.*.subject_id'      => 'required|exists:subjects,id',
            'marks.*.marks_obtained'  => 'nullable|numeric|min:0|max:100',
            'marks.*.is_absent'       => 'boolean',
            'marks.*.remarks'         => 'nullable|string|max:200',
        ]);

        $schoolId = $this->getSchoolId();
        $grading  = new GradingService($schoolId);

        DB::transaction(function () use ($data, $exam, $schoolId, $grading) {
            foreach ($data['marks'] as $row) {
                $marksObtained = $row['is_absent'] ? null : ($row['marks_obtained'] ?? null);
                $graded        = $marksObtained !== null ? $grading->calculate((float) $marksObtained, 100) : ['grade' => null, 'gpa' => null];

                Mark::updateOrCreate(
                    [
                        'exam_id'    => $exam->id,
                        'student_id' => $row['student_id'],
                        'subject_id' => $row['subject_id'],
                    ],
                    [
                        'school_id'      => $schoolId,
                        'marks_obtained' => $marksObtained,
                        'grade'          => $graded['grade'],
                        'gpa'            => $graded['gpa'],
                        'is_absent'      => $row['is_absent'] ?? false,
                        'remarks'        => $row['remarks'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Marks saved successfully.');
    }

    /**
     * Results / merit list for an exam.
     */
    public function results(Request $request, Exam $exam): Response
    {
        $sectionId = $request->section_id;
        $subjects  = Subject::where('class_id', $exam->class_id)->orderBy('name')->get();

        $students = Student::where('class_id', $exam->class_id)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->where('status', 'active')
            ->with('section:id,name')
            ->orderBy('roll_no')
            ->get(['id', 'first_name', 'last_name', 'roll_no', 'section_id']);

        $allMarks = Mark::where('exam_id', $exam->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id')
            ->map(fn ($marks) => $marks->keyBy('subject_id'));

        // Build result rows
        $results = $students->map(function ($student) use ($subjects, $allMarks) {
            $studentMarks = $allMarks[$student->id] ?? collect();
            $total        = 0;
            $obtained     = 0;
            $failed       = false;
            $subjectRows  = [];

            foreach ($subjects as $subject) {
                $mark = $studentMarks[$subject->id] ?? null;
                $mo   = $mark && !$mark->is_absent ? (float) $mark->marks_obtained : 0;
                $total    += $subject->full_marks;
                $obtained += $mo;
                if ($mark && ($mark->grade === 'F' || $mark->is_absent)) $failed = true;

                $subjectRows[] = [
                    'subject_id'     => $subject->id,
                    'marks_obtained' => $mark?->marks_obtained,
                    'grade'          => $mark?->grade,
                    'gpa'            => $mark?->gpa,
                    'is_absent'      => $mark?->is_absent ?? false,
                ];
            }

            $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0;
            $avgGpa     = $studentMarks->whereNotNull('gpa')->avg('gpa');

            return [
                'student'    => $student,
                'marks'      => $subjectRows,
                'total'      => $total,
                'obtained'   => round($obtained, 2),
                'percentage' => $percentage,
                'avg_gpa'    => $avgGpa ? round($avgGpa, 2) : null,
                'failed'     => $failed,
                'rank'       => 0, // set after sort
            ];
        })->sortByDesc('obtained')->values();

        // Assign rank
        $results = $results->map(function ($row, $idx) {
            $row['rank'] = $idx + 1;
            return $row;
        });

        return Inertia::render('SchoolAdmin/Exams/Results', [
            'exam'       => $exam->load('schoolClass:id,name'),
            'subjects'   => $subjects,
            'results'    => $results->values(),
            'sections'   => Section::where('class_id', $exam->class_id)->orderBy('name')->get(['id', 'name']),
            'gradeScale' => GradeScale::orderByDesc('min_marks')->get(),
            'filters'    => ['section_id' => $sectionId],
        ]);
    }

    /**
     * Grade scales management page.
     */
    public function gradeScales(): Response
    {
        return Inertia::render('SchoolAdmin/Exams/GradeScales', [
            'scales' => GradeScale::orderBy('sort_order')->get(),
        ]);
    }

    public function saveGradeScale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grade'      => 'required|string|max:10',
            'gpa'        => 'required|numeric|min:0|max:5',
            'min_marks'  => 'required|numeric|min:0|max:100',
            'max_marks'  => 'required|numeric|min:0|max:100',
            'remarks'    => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        GradeScale::create(array_merge($data, ['school_id' => $this->getSchoolId()]));
        return back()->with('success', 'Grade added.');
    }

    public function updateGradeScale(Request $request, GradeScale $gradeScale): RedirectResponse
    {
        $data = $request->validate([
            'grade'      => 'required|string|max:10',
            'gpa'        => 'required|numeric|min:0|max:5',
            'min_marks'  => 'required|numeric|min:0|max:100',
            'max_marks'  => 'required|numeric|min:0|max:100',
            'remarks'    => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);
        $gradeScale->update($data);
        return back()->with('success', 'Grade updated.');
    }

    public function deleteGradeScale(GradeScale $gradeScale): RedirectResponse
    {
        $gradeScale->delete();
        return back()->with('success', 'Grade deleted.');
    }
}
