<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LessonPlan;
use App\Models\OnlineClass;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Syllabus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeworkController extends Controller
{
    // ── Homework ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $homework = Homework::with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,first_name,last_name'])
            ->withCount('submissions')
            ->where('school_id', $sid)
            ->when($request->class_id,   fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
            ->latest('due_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Homework/Index', [
            'homework' => $homework,
            'classes'  => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'staff'    => Staff::where('school_id', $sid)->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'filters'  => $request->only('class_id', 'subject_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'subject_id'  => 'required|exists:subjects,id',
            'teacher_id'  => 'nullable|exists:staff,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
        ]);

        Homework::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Homework assigned.');
    }

    public function update(Request $request, Homework $homework)
    {
        $data = $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'subject_id'  => 'required|exists:subjects,id',
            'teacher_id'  => 'nullable|exists:staff,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'is_active'   => 'boolean',
        ]);

        $homework->update($data);
        return back()->with('success', 'Homework updated.');
    }

    public function destroy(Homework $homework)
    {
        $homework->delete();
        return back()->with('success', 'Homework deleted.');
    }

    public function submissions(Request $request, Homework $homework)
    {
        $homework->load(['schoolClass:id,name', 'subject:id,name', 'teacher:id,first_name,last_name']);

        $submissions = HomeworkSubmission::with('student:id,first_name,last_name,admission_no')
            ->where('homework_id', $homework->id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        return Inertia::render('SchoolAdmin/Homework/Submissions', [
            'homework'    => $homework,
            'submissions' => $submissions,
            'filters'     => $request->only('status'),
        ]);
    }

    public function reviewSubmission(Request $request, HomeworkSubmission $submission)
    {
        $data = $request->validate([
            'status'          => 'required|in:reviewed,returned',
            'teacher_remarks' => 'nullable|string|max:1000',
        ]);

        $submission->update($data);
        return back()->with('success', 'Submission reviewed.');
    }

    // ── Lesson Plans ──────────────────────────────────────────────

    public function lessonPlans(Request $request)
    {
        $sid = $this->getSchoolId();

        $plans = LessonPlan::with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,first_name,last_name'])
            ->where('school_id', $sid)
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->when($request->class_id,  fn ($q) => $q->where('class_id', $request->class_id))
            ->latest('week_start')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Homework/LessonPlans', [
            'plans'    => $plans,
            'classes'  => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'staff'    => Staff::where('school_id', $sid)->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'filters'  => $request->only('status', 'class_id'),
        ]);
    }

    public function storeLessonPlan(Request $request)
    {
        $data = $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'subject_id'       => 'required|exists:subjects,id',
            'teacher_id'       => 'nullable|exists:staff,id',
            'title'            => 'required|string|max:255',
            'objectives'       => 'nullable|string',
            'content'          => 'nullable|string',
            'teaching_methods' => 'nullable|string',
            'resources'        => 'nullable|string',
            'week_start'       => 'required|date',
        ]);

        LessonPlan::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Lesson plan created.');
    }

    public function reviewLessonPlan(Request $request, LessonPlan $lessonPlan)
    {
        $data = $request->validate([
            'action'            => 'required|in:approved,rejected,submitted',
            'reviewer_feedback' => 'nullable|string|max:1000',
        ]);

        $lessonPlan->update([
            'status'            => $data['action'],
            'reviewer_feedback' => $data['reviewer_feedback'] ?? null,
            'reviewed_by'       => auth()->id(),
            'reviewed_at'       => now(),
        ]);

        return back()->with('success', 'Lesson plan ' . $data['action'] . '.');
    }

    public function destroyLessonPlan(LessonPlan $lessonPlan)
    {
        $lessonPlan->delete();
        return back()->with('success', 'Lesson plan deleted.');
    }

    // ── Syllabus ──────────────────────────────────────────────────

    public function syllabi(Request $request)
    {
        $sid = $this->getSchoolId();

        $syllabi = Syllabus::with(['schoolClass:id,name', 'subject:id,name'])
            ->where('school_id', $sid)
            ->when($request->class_id,      fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->orderBy('completion_percent', 'asc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Homework/Syllabi', [
            'syllabi'  => $syllabi,
            'classes'  => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'filters'  => $request->only('class_id', 'academic_year'),
        ]);
    }

    public function storeSyllabus(Request $request)
    {
        $data = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'subject_id'    => 'required|exists:subjects,id',
            'academic_year' => 'required|string|max:20',
            'title'         => 'required|string|max:255',
            'topics'        => 'nullable|array',
            'topics.*.title'  => 'required|string|max:255',
            'topics.*.covered'=> 'boolean',
        ]);

        $data['school_id'] = $this->getSchoolId();

        $syllabus = Syllabus::create($data);
        $syllabus->recalculateCompletion();
        $syllabus->save();

        return back()->with('success', 'Syllabus created.');
    }

    public function updateSyllabus(Request $request, Syllabus $syllabus)
    {
        $data = $request->validate([
            'title'  => 'required|string|max:255',
            'topics' => 'nullable|array',
            'topics.*.title'  => 'required|string|max:255',
            'topics.*.covered'=> 'boolean',
        ]);

        $syllabus->fill($data);
        $syllabus->recalculateCompletion();
        $syllabus->save();

        return back()->with('success', 'Syllabus updated.');
    }

    // ── Online Classes ────────────────────────────────────────────

    public function onlineClasses(Request $request)
    {
        $sid = $this->getSchoolId();

        $classes = OnlineClass::with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,first_name,last_name'])
            ->where('school_id', $sid)
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Homework/OnlineClasses', [
            'onlineClasses' => $classes,
            'classes'       => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects'      => Subject::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'staff'         => Staff::where('school_id', $sid)->where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'filters'       => $request->only('status', 'class_id'),
        ]);
    }

    public function storeOnlineClass(Request $request)
    {
        $data = $request->validate([
            'class_id'         => 'required|exists:classes,id',
            'subject_id'       => 'required|exists:subjects,id',
            'teacher_id'       => 'nullable|exists:staff,id',
            'title'            => 'required|string|max:255',
            'platform'         => 'required|in:zoom,google_meet,jitsi,other',
            'meeting_url'      => 'required|url|max:500',
            'meeting_id'       => 'nullable|string|max:100',
            'passcode'         => 'nullable|string|max:50',
            'scheduled_at'     => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:480',
        ]);

        OnlineClass::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Online class scheduled.');
    }

    public function updateOnlineClassStatus(Request $request, OnlineClass $onlineClass)
    {
        $data = $request->validate([
            'status' => 'required|in:scheduled,live,completed,cancelled',
        ]);

        $onlineClass->update($data);
        return back()->with('success', 'Status updated.');
    }

    public function destroyOnlineClass(OnlineClass $onlineClass)
    {
        $onlineClass->delete();
        return back()->with('success', 'Online class removed.');
    }
}
