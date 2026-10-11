<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\LessonNoteFormat;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\AiService;
use App\Services\LessonNoteService;
use App\Services\ReportCardService;
use App\Models\ReportCardDesign;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lesson notes (the lesson plans table, now written under the school's own headings), the schemes
 * of work they are planned from, and the note formats. Teachers write and submit notes; heads
 * (lessons.approve) approve or send them back, and choose the formats.
 */
class LessonNoteController extends Controller
{
    public function __construct(private LessonNoteService $notes, private AiService $ai) {}

    // ── Lesson notes ──────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $sid = $this->getSchoolId();
        $user = $request->user();
        $mine = $request->boolean('mine') || ! $user->can('lessons.approve');
        $staffId = $this->staffId($user);

        $notes = LessonPlan::with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,first_name,last_name', 'creator:id,name', 'term:id,name'])
            ->where('school_id', $sid)
            ->when($mine, fn ($q) => $q->where(fn ($w) => $w->where('created_by', $user->id)->when($staffId, fn ($o) => $o->orWhere('teacher_id', $staffId))))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->term_id, fn ($q) => $q->where('term_id', $request->term_id))
            ->orderByDesc('week_start')->orderByDesc('id')
            ->paginate(20)->withQueryString()
            ->through(fn (LessonPlan $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'class' => $n->schoolClass?->name,
                'subject' => $n->subject?->name,
                'term' => $n->term?->name,
                'week' => $n->week,
                'week_start' => $n->week_start?->toDateString(),
                'teacher' => $n->teacherName(),
                'status' => $n->status,
                'ai_drafted' => $n->ai_drafted,
            ]);

        $counts = LessonPlan::where('school_id', $sid)
            ->when($mine, fn ($q) => $q->where(fn ($w) => $w->where('created_by', $user->id)->when($staffId, fn ($o) => $o->orWhere('teacher_id', $staffId))))
            ->pluck('status')->countBy();

        return Inertia::render('SchoolAdmin/LessonNotes/Index', [
            'notes' => $notes,
            'counts' => $counts,
            ...$this->choices($sid),
            'filters' => $request->only('status', 'class_id', 'subject_id', 'term_id', 'mine'),
            'canCreate' => $user->can('lessons.create'),
            'canApprove' => $user->can('lessons.approve'),
            'mine' => $mine,
        ]);
    }

    public function create(Request $request): Response
    {
        $sid = $this->getSchoolId();
        LessonNoteFormat::ensureFor($sid);
        $scheme = $request->integer('scheme') ? SchemeOfWork::find($request->integer('scheme')) : null;
        $week = $scheme ? max(1, $request->integer('week', 1)) : null;
        $planned = $scheme?->week((int) $week);
        $format = LessonNoteFormat::where('school_id', $sid)->where('is_default', true)->first() ?? LessonNoteFormat::where('school_id', $sid)->orderBy('id')->first();

        return $this->editor(null, [
            'class_id' => $scheme?->class_id,
            'subject_id' => $scheme?->subject_id,
            'term_id' => $scheme?->term_id ?? Term::where('school_id', $sid)->where('is_current', true)->value('id'),
            'scheme_of_work_id' => $scheme?->id,
            'lesson_note_format_id' => $format?->id,
            'week' => $week,
            'week_start' => $this->weekStart($scheme?->term_id ? Term::find($scheme->term_id) : null, $week),
            'duration_minutes' => 40,
            'title' => $planned['topic'] ?? '',
            'sections' => (object) [],
            'status' => 'draft',
            'ai_drafted' => false,
        ], $request->user());
    }

    public function edit(Request $request, LessonPlan $note): Response
    {
        $note->loadMissing('format');

        return $this->editor($note, [
            'class_id' => $note->class_id,
            'subject_id' => $note->subject_id,
            'term_id' => $note->term_id,
            'scheme_of_work_id' => $note->scheme_of_work_id,
            'lesson_note_format_id' => $note->lesson_note_format_id,
            'week' => $note->week,
            'week_start' => $note->week_start?->toDateString(),
            'duration_minutes' => $note->duration_minutes,
            'title' => $note->title,
            // Old notes are opened with their old columns as headings
            'sections' => (object) collect($note->body())->mapWithKeys(fn ($s) => [$s['key'] => $s['text']])->all(),
            'status' => $note->status,
            'ai_drafted' => $note->ai_drafted,
        ], $request->user());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $note = LessonPlan::create($data + [
            'school_id' => $this->getSchoolId(),
            'created_by' => $request->user()->id,
            'teacher_id' => $this->staffId($request->user()),
            'status' => 'draft',
        ]);
        $this->maybeSubmit($request, $note);

        return redirect()->route('school.lesson-notes.edit', $note)->with('success', $note->status === 'submitted' ? 'Lesson note saved and sent for approval.' : 'Lesson note saved.');
    }

    public function update(Request $request, LessonPlan $note): RedirectResponse
    {
        abort_unless($this->canEdit($request->user(), $note), 403);
        $note->update($this->validated($request) + ['status' => 'draft']);
        $this->maybeSubmit($request, $note);

        return back()->with('success', $note->status === 'submitted' ? 'Lesson note saved and sent for approval.' : 'Lesson note saved.');
    }

    /** Approve, or send back with a reason. Only a submitted note can be reviewed. */
    public function review(Request $request, LessonPlan $note): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:approved,rejected',
            'reviewer_feedback' => 'nullable|string|max:1000|required_if:action,rejected',
        ], ['reviewer_feedback.required_if' => 'Say what the teacher should change.']);
        if ($note->status !== 'submitted') {
            throw ValidationException::withMessages(['action' => 'Only a note sent for approval can be approved or sent back.']);
        }
        $note->update([
            'status' => $data['action'],
            'reviewer_feedback' => $data['reviewer_feedback'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', $data['action'] === 'approved' ? 'Lesson note approved.' : 'Lesson note sent back to the teacher.');
    }

    public function destroy(Request $request, LessonPlan $note): RedirectResponse
    {
        abort_unless($this->canEdit($request->user(), $note) && $note->status !== 'approved', 403);
        $note->delete();

        return redirect()->route('school.lesson-notes.index')->with('success', 'Lesson note deleted.');
    }

    /** The AI's draft of the note's sections, for the editor. Nothing is saved. */
    public function aiDraft(Request $request): JsonResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $sid)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)],
            'scheme_of_work_id' => ['nullable', Rule::exists('schemes_of_work', 'id')->where('school_id', $sid)],
            'lesson_note_format_id' => ['nullable', Rule::exists('lesson_note_formats', 'id')->where('school_id', $sid)],
            'week' => 'nullable|integer|min:1|max:'.LessonNoteService::MAX_WEEKS,
            'topic' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:10|max:300',
            'instructions' => 'nullable|string|max:1500',
        ]);
        $format = ! empty($data['lesson_note_format_id']) ? LessonNoteFormat::find($data['lesson_note_format_id']) : null;

        return response()->json(['sections' => (object) $this->notes->draft($sid, $request->user(), $this->notes->sectionsOf($format), $data)]);
    }

    /** The note as an A4 PDF under the school's name, logo and address */
    public function pdf(LessonPlan $note, ReportCardService $cards): \Illuminate\Http\Response
    {
        $note->loadMissing(['schoolClass:id,name', 'subject:id,name', 'term:id,name', 'teacher:id,first_name,last_name', 'creator:id,name', 'format', 'reviewer:id,name']);
        $design = $cards->design(ReportCardDesign::forClass($note->school_id, $note->class_id));

        return Pdf::loadView('lesson-notes.note', [
            'note' => $note,
            'body' => $note->body(),
            'school' => $cards->school($note->school_id),
            'color' => $design['primary'],
        ])->setPaper('a4')->stream('lesson-note-'.$note->id.'.pdf');
    }

    // ── Schemes of work ───────────────────────────────────────────

    public function schemes(Request $request): Response
    {
        $sid = $this->getSchoolId();
        $schemes = SchemeOfWork::with(['schoolClass:id,name', 'subject:id,name', 'term:id,name'])->withCount('notes')
            ->where('school_id', $sid)
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->term_id, fn ($q) => $q->where('term_id', $request->term_id))
            ->latest()->get()
            ->map(fn (SchemeOfWork $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'class' => $s->schoolClass?->name,
                'subject' => $s->subject?->name,
                'term' => $s->term?->name,
                'weeks' => count($s->weeks ?? []),
                'notes' => $s->notes_count,
                'has_file' => (bool) $s->source_path,
            ]);

        return Inertia::render('SchoolAdmin/LessonNotes/Schemes', [
            'schemes' => $schemes,
            ...$this->choices($sid),
            'filters' => $request->only('class_id', 'subject_id', 'term_id'),
            'canManage' => $request->user()->can('lessons.create'),
        ]);
    }

    public function scheme(Request $request, ?SchemeOfWork $scheme = null): Response
    {
        $sid = $this->getSchoolId();
        $notes = $scheme ? LessonPlan::where('scheme_of_work_id', $scheme->id)->get(['id', 'week', 'status'])->groupBy('week') : collect();

        return Inertia::render('SchoolAdmin/LessonNotes/Scheme', [
            'scheme' => $scheme ? [
                'id' => $scheme->id,
                'class_id' => $scheme->class_id,
                'subject_id' => $scheme->subject_id,
                'term_id' => $scheme->term_id,
                'title' => $scheme->title,
                'weeks' => $scheme->weeks ?? [],
                'source_name' => $scheme->source_name,
                'notes' => $notes->map(fn ($n) => $n->map(fn ($x) => ['id' => $x->id, 'status' => $x->status])->values()),
            ] : null,
            ...$this->choices($sid),
            'currentTerm' => Term::where('school_id', $sid)->where('is_current', true)->value('id'),
            'canManage' => $request->user()->can('lessons.create'),
            'ai' => $this->aiState($sid),
        ]);
    }

    public function storeScheme(Request $request): RedirectResponse
    {
        $data = $this->validatedScheme($request);
        $scheme = SchemeOfWork::create($data + ['school_id' => $this->getSchoolId(), 'created_by' => $request->user()->id]);
        $this->keepFile($request, $scheme);

        return redirect()->route('school.lesson-notes.schemes.show', $scheme)->with('success', 'Scheme of work saved.');
    }

    public function updateScheme(Request $request, SchemeOfWork $scheme): RedirectResponse
    {
        $scheme->update($this->validatedScheme($request));
        $this->keepFile($request, $scheme);

        return back()->with('success', 'Scheme of work saved.');
    }

    public function destroyScheme(SchemeOfWork $scheme): RedirectResponse
    {
        if ($scheme->source_path) {
            Storage::disk('private')->delete($scheme->source_path);
        }
        $scheme->delete();

        return redirect()->route('school.lesson-notes.schemes')->with('success', 'Scheme of work deleted. Lesson notes written from it are kept.');
    }

    /**
     * Reads an uploaded or pasted scheme into weeks for the teacher to check. Typed "Week 1: …" lines are
     * read here without the AI; anything else (a PDF, a Word file, a table) goes to the AI. Nothing is saved.
     */
    public function readScheme(Request $request): JsonResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'file' => 'nullable|file|max:10240|mimes:pdf,docx,txt',
            'text' => 'nullable|string|max:60000',
            'use_ai' => 'boolean',
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $sid)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)],
        ], ['file.mimes' => 'Upload the scheme as a PDF, a Word (.docx) file or a text file.']);

        $source = $request->file('file') ? $this->notes->readUpload($request->file('file')) : ['text' => $data['text'] ?? '', 'file' => null];

        if (! $request->boolean('use_ai')) {
            if ($source['file']) {
                throw ValidationException::withMessages(['source' => 'A PDF can only be read by the AI. Switch on "Read it with AI", or type the weeks in.']);
            }
            $weeks = $this->notes->parseTyped((string) $source['text']);
            if ($weeks === []) {
                throw ValidationException::withMessages(['source' => 'No weeks found. Start each line with the week number, for example "Week 1: Number bases".']);
            }

            return response()->json(['weeks' => $weeks]);
        }

        return response()->json(['weeks' => $this->notes->readWithAi(
            $sid, $request->user(), $source['text'], $source['file'],
            (string) Subject::whereKey($data['subject_id'])->value('name'),
            (string) SchoolClass::whereKey($data['class_id'])->value('name'),
            ! empty($data['term_id']) ? Term::whereKey($data['term_id'])->value('name') : null,
        )]);
    }

    /** The scheme file as the school uploaded it */
    public function schemeFile(SchemeOfWork $scheme): StreamedResponse
    {
        abort_unless($scheme->source_path && Storage::disk('private')->exists($scheme->source_path), 404);

        return Storage::disk('private')->download($scheme->source_path, $scheme->source_name ?: basename($scheme->source_path));
    }

    // ── Formats ───────────────────────────────────────────────────

    public function formats(Request $request): Response
    {
        $sid = $this->getSchoolId();
        LessonNoteFormat::ensureFor($sid);
        $used = LessonPlan::where('school_id', $sid)->whereNotNull('lesson_note_format_id')->pluck('lesson_note_format_id')->countBy();

        return Inertia::render('SchoolAdmin/LessonNotes/Formats', [
            'formats' => LessonNoteFormat::where('school_id', $sid)->orderByDesc('is_default')->orderBy('name')->get()->map(fn ($f) => [
                'id' => $f->id, 'name' => $f->name, 'sections' => $f->sections, 'is_default' => $f->is_default, 'notes' => (int) ($used[$f->id] ?? 0),
            ]),
            'canManage' => $request->user()->can('lessons.approve'),
        ]);
    }

    public function storeFormat(Request $request): RedirectResponse
    {
        $data = $this->validatedFormat($request);
        $format = LessonNoteFormat::create([
            'school_id' => $this->getSchoolId(),
            'name' => $data['name'],
            'sections' => LessonNoteFormat::withKeys($data['sections']),
            'is_default' => false,
        ]);
        if ($request->boolean('is_default')) {
            $this->makeDefault($format);
        }

        return back()->with('success', 'Format saved.');
    }

    public function updateFormat(Request $request, LessonNoteFormat $format): RedirectResponse
    {
        $data = $this->validatedFormat($request, $format);
        $format->update(['name' => $data['name'], 'sections' => LessonNoteFormat::withKeys($data['sections'], $format->sections ?? [])]);
        if ($request->boolean('is_default')) {
            $this->makeDefault($format);
        }

        return back()->with('success', 'Format saved.');
    }

    public function destroyFormat(LessonNoteFormat $format): RedirectResponse
    {
        if ($format->is_default) {
            throw ValidationException::withMessages(['format' => 'Make another format the default before deleting this one.']);
        }
        if (LessonPlan::where('lesson_note_format_id', $format->id)->exists()) {
            throw ValidationException::withMessages(['format' => 'Lesson notes have been written in this format, so it is kept. You can rename it.']);
        }
        $format->delete();

        return back()->with('success', 'Format deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function editor(?LessonPlan $note, array $values, User $user): Response
    {
        $sid = $this->getSchoolId();
        LessonNoteFormat::ensureFor($sid);
        $schemes = SchemeOfWork::with(['schoolClass:id,name', 'subject:id,name', 'term:id,name'])->where('school_id', $sid)->latest()->get();

        return Inertia::render('SchoolAdmin/LessonNotes/Note', [
            'note' => $note ? [
                'id' => $note->id,
                'reviewer_feedback' => $note->reviewer_feedback,
                'reviewed_by' => $note->reviewer?->name,
                'reviewed_at' => $note->reviewed_at?->toDateTimeString(),
            ] : null,
            'values' => $values,
            ...$this->choices($sid),
            'formats' => LessonNoteFormat::where('school_id', $sid)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'sections', 'is_default']),
            'schemes' => $schemes->map(fn (SchemeOfWork $s) => [
                'id' => $s->id,
                'label' => trim(($s->subject?->name ?? '').' · '.($s->schoolClass?->name ?? '').($s->term ? ' · '.$s->term->name : '')),
                'class_id' => $s->class_id,
                'subject_id' => $s->subject_id,
                'term_id' => $s->term_id,
                'weeks' => $s->weeks ?? [],
            ]),
            'termStarts' => Term::where('school_id', $sid)->orderBy('start_date')->get(['id', 'start_date'])->mapWithKeys(fn ($t) => [$t->id => $t->start_date?->toDateString()]),
            'canEdit' => $note ? $this->canEdit($user, $note) : $user->can('lessons.create'),
            'canApprove' => $user->can('lessons.approve'),
            'ai' => $this->aiState($sid),
        ]);
    }

    private function choices(int $sid): array
    {
        return [
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'subjects' => Subject::picker($sid),
            'terms' => Term::with('academicYear:id,name')->where('school_id', $sid)->orderByDesc('start_date')->get(['id', 'name', 'academic_year_id', 'is_current'])
                ->map(fn ($t) => ['id' => $t->id, 'name' => trim($t->name.' '.($t->academicYear?->name ?? '')), 'is_current' => (bool) $t->is_current]),
        ];
    }

    private function aiState(int $sid): array
    {
        return [
            'shown' => $this->ai->schoolState($sid)['platform_ready'],
            'reason' => $this->ai->unavailable($sid, 'lesson_notes'),
        ];
    }

    private function validated(Request $request): array
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $sid)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)],
            'scheme_of_work_id' => ['nullable', Rule::exists('schemes_of_work', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'lesson_note_format_id' => ['required', Rule::exists('lesson_note_formats', 'id')->where('school_id', $sid)],
            'week' => 'nullable|integer|min:1|max:'.LessonNoteService::MAX_WEEKS,
            'week_start' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:10|max:300',
            'title' => 'required|string|max:255',
            'sections' => 'present|array',
            'sections.*' => 'nullable|string|max:8000',
            'ai_drafted' => 'boolean',
        ], ['title.required' => 'Type the topic of the lesson.']);

        // The format's headings in order, then any other text the note still has (from an older
        // format, or an old lesson plan), so changing a format never throws writing away
        $keys = collect(LessonNoteFormat::find($data['lesson_note_format_id'])->sections)->pluck('key');
        $sections = $keys->mapWithKeys(fn ($k) => [$k => trim((string) ($data['sections'][$k] ?? ''))])->all();
        foreach ($data['sections'] as $key => $text) {
            if (! isset($sections[$key]) && preg_match('/^[a-z0-9_]{1,40}$/', (string) $key) && trim((string) $text) !== '' && count($sections) < 30) {
                $sections[$key] = trim((string) $text);
            }
        }
        $data['sections'] = $sections;
        $data['ai_drafted'] = (bool) ($data['ai_drafted'] ?? false);

        return $data;
    }

    private function maybeSubmit(Request $request, LessonPlan $note): void
    {
        if (! $request->boolean('submit')) {
            return;
        }
        if (collect($note->sections)->filter(fn ($t) => trim((string) $t) !== '')->isEmpty()) {
            throw ValidationException::withMessages(['sections' => 'Write the note before sending it for approval.']);
        }
        $note->update(['status' => 'submitted', 'reviewer_feedback' => null]);
    }

    /** Drafts and notes sent back can be changed by the teacher who wrote them, or by a head */
    private function canEdit(User $user, LessonPlan $note): bool
    {
        if (! $user->can('lessons.create') || ! in_array($note->status, ['draft', 'rejected'], true)) {
            return false;
        }
        if ($user->can('lessons.approve')) {
            return true;
        }
        $staffId = $this->staffId($user);

        return $note->created_by === $user->id || ($staffId && $note->teacher_id === $staffId);
    }

    private function staffId(User $user): ?int
    {
        return Staff::where('user_id', $user->id)->value('id');
    }

    /** The Monday of the given week of the term, or this Monday */
    private function weekStart(?Term $term, ?int $week): string
    {
        if ($term?->start_date && $week) {
            return $term->start_date->copy()->startOfWeek()->addWeeks($week - 1)->toDateString();
        }

        return now()->startOfWeek()->toDateString();
    }

    private function validatedScheme(Request $request): array
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $sid)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)],
            'title' => 'nullable|string|max:255',
            'weeks' => 'required|array|min:1|max:'.LessonNoteService::MAX_WEEKS,
            'weeks.*.week' => 'required|integer|min:1|max:'.LessonNoteService::MAX_WEEKS,
            'weeks.*.topic' => 'required|string|max:255',
            'weeks.*.details' => 'nullable|string|max:2000',
            'file' => 'nullable|file|max:10240|mimes:pdf,docx,txt',
        ], [
            'weeks.required' => 'Add at least one week.',
            'weeks.*.topic.required' => 'Every week needs a topic.',
        ]);
        $weeks = $this->notes->clean($data['weeks']);
        if (count($weeks) !== count($data['weeks'])) {
            throw ValidationException::withMessages(['weeks' => 'Each week number can be used only once.']);
        }
        $subject = Subject::whereKey($data['subject_id'])->value('name');
        $class = SchoolClass::whereKey($data['class_id'])->value('name');

        return [
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'],
            'term_id' => $data['term_id'] ?? null,
            'title' => trim((string) ($data['title'] ?? '')) ?: "{$subject} · {$class}",
            'weeks' => $weeks,
        ];
    }

    private function keepFile(Request $request, SchemeOfWork $scheme): void
    {
        if (! $file = $request->file('file')) {
            return;
        }
        if ($scheme->source_path) {
            Storage::disk('private')->delete($scheme->source_path);
        }
        $scheme->update([
            'source_path' => $file->store('schemes-of-work/'.$scheme->school_id, 'private'),
            'source_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ]);
    }

    private function validatedFormat(Request $request, ?LessonNoteFormat $format = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('lesson_note_formats', 'name')->where('school_id', $this->getSchoolId())->ignore($format?->id)],
            'sections' => 'required|array|min:1|max:15',
            'sections.*.label' => 'required|string|max:80',
            'sections.*.hint' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ], [
            'name.unique' => 'There is already a format with this name.',
            'sections.*.label.required' => 'Every heading needs a name.',
        ]);
    }

    private function makeDefault(LessonNoteFormat $format): void
    {
        LessonNoteFormat::where('school_id', $format->school_id)->whereKeyNot($format->id)->update(['is_default' => false]);
        $format->update(['is_default' => true]);
    }
}
