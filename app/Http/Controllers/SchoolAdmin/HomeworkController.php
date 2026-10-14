<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
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

    // ── Syllabus ──────────────────────────────────────────────────

    public function syllabi(Request $request, \App\Services\AiService $ai)
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
            'ai'       => ['shown' => $ai->schoolState($sid)['platform_ready'], 'reason' => $ai->unavailable($sid, 'lesson_notes')],
        ]);
    }

    /**
     * A syllabus document (PDF, Word or text) read by the AI into a title, class, subject, year and topic list, so the
     * "New syllabus" form can be filled in for checking. Nothing is saved here.
     */
    public function readSyllabus(Request $request, \App\Services\LessonNoteService $notes)
    {
        $sid = $this->getSchoolId();
        $request->validate([
            'file' => 'nullable|file|max:10240|mimes:pdf,docx,txt',
            'text' => 'nullable|string|max:60000',
        ], ['file.mimes' => 'Upload the syllabus as a PDF, a Word (.docx) file or a text file.']);

        $source = $request->file('file') ? $notes->readUpload($request->file('file')) : ['text' => (string) $request->input('text', ''), 'file' => null];
        $classes = SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->pluck('name', 'id')->all();
        $subjects = Subject::where('school_id', $sid)->orderBy('name')->pluck('name', 'id')->all();

        $answer = $notes->readDocument($sid, $request->user(), 'syllabus', $source['text'], $source['file'], [
            'classes' => array_values(array_unique($classes)), 'subjects' => array_values(array_unique($subjects)), 'terms' => [],
        ]);

        $topics = collect($answer['topics'] ?? [])->map(fn ($t) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $t)), 0, 255))->filter()->unique()->take(200)->values();
        if ($topics->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['source' => 'The AI could not find a list of topics in this document. Check that it is the syllabus, or add the topics by hand.']);
        }

        return response()->json([
            'class_id' => $notes->matchId($answer['class'] ?? null, $classes),
            'subject_id' => $notes->matchId($answer['subject'] ?? null, $subjects),
            'academic_year' => mb_substr(trim((string) ($answer['academic_year'] ?? '')), 0, 20),
            'title' => mb_substr(trim((string) ($answer['title'] ?? '')), 0, 255),
            'topics' => $topics->map(fn ($t) => ['title' => $t, 'covered' => false])->all(),
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
