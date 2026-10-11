<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\IdCardDesign;
use App\Models\ReportCardSigner;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentIdCard;
use App\Services\IdCardService;
use App\Support\EmbeddedImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Student ID cards: the register (who has a card, who still needs one or a photo), issuing cards to a whole
 * class or school at once, replacing a lost card, printing on A4 sheets or one card per page for a PVC card
 * printer, the card designs, and the scan screen.
 */
class IdCardController extends Controller
{
    /** Most cards in one PDF, so a print never runs long; bigger runs are done a class at a time */
    public const MAX_PER_PRINT = 300;

    public function __construct(private IdCardService $cards) {}

    public function index(Request $request): Response
    {
        $filter = $request->string('show')->toString();
        $today = now()->toDateString();
        $valid = fn ($q) => $q->where('status', 'active')->where(fn ($w) => $w->whereNull('expires_on')->orWhereDate('expires_on', '>=', $today));

        $students = Student::with(['schoolClass:id,name', 'section:id,name', 'house:id,name,colour', 'idCards' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->when($request->class_id, fn ($q, $c) => $q->where('class_id', $c))
            ->when($filter === 'without', fn ($q) => $q->whereDoesntHave('idCards', $valid))
            ->when($filter === 'with', fn ($q) => $q->whereHas('idCards', $valid))
            ->when($filter === 'no_photo', fn ($q) => $q->whereNull('photo'))
            ->when($filter === 'not_printed', fn ($q) => $q->whereHas('idCards', fn ($c) => $valid($c)->where('prints', 0)))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%")))
            ->orderBy('class_id')->orderBy('first_name')->orderBy('last_name')
            ->paginate(50)->withQueryString();

        $active = Student::where('status', 'active');
        $user = $request->user();

        return Inertia::render('SchoolAdmin/IdCards/Index', [
            'students' => [
                'data' => collect($students->items())->map(function (Student $s) {
                    $card = $s->idCards->first();

                    return [
                        'id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no,
                        'class' => trim(($s->schoolClass?->name ?? '').' '.($s->section?->name ?? '')),
                        'house' => $s->house ? ['name' => $s->house->name, 'colour' => $s->house->colour] : null,
                        'has_photo' => (bool) $s->photo, 'photo_url' => $s->photo_url,
                        'card' => $card ? ['id' => $card->id, 'card_no' => $card->card_no, 'state' => $card->state(), 'expires_on' => $card->expires_on?->toDateString(), 'prints' => $card->prints] : null,
                    ];
                }),
                'meta' => ['total' => $students->total(), 'from' => $students->firstItem(), 'to' => $students->lastItem(), 'last_page' => $students->lastPage()],
                'links' => ['prev' => $students->previousPageUrl(), 'next' => $students->nextPageUrl()],
            ],
            'stats' => [
                'students' => (clone $active)->count(),
                'with_card' => (clone $active)->whereHas('idCards', $valid)->count(),
                'without_card' => (clone $active)->whereDoesntHave('idCards', $valid)->count(),
                'without_photo' => (clone $active)->whereNull('photo')->count(),
            ],
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'designs' => $this->designList(),
            'filters' => $request->only('class_id', 'show', 'search'),
            'max_per_print' => self::MAX_PER_PRINT,
            'can' => ['issue' => $user->can('students.idcard'), 'scan' => $user->can('students.view'), 'design' => $user->can('settings.edit')],
        ]);
    }

    /** Cards for every active student (or chosen classes) who has no working card: new students, and expired cards */
    public function issueMissing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'class_ids' => 'array',
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())],
        ]);
        $students = Student::where('status', 'active')->when($data['class_ids'] ?? [], fn ($q, $ids) => $q->whereIn('class_id', $ids))->get();
        $made = $this->cards->issueMissing($students, $request->user());

        return back()->with('success', $made === 0 ? 'Everyone already has a working card.' : "{$made} new card".($made === 1 ? '' : 's').' made. Print them from the list.');
    }

    /** A new card for one student (first card, or replacing a lost or damaged one) */
    public function issue(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:200']);
        $hadOne = StudentIdCard::where('student_id', $student->id)->where('status', 'active')->exists();
        if ($hadOne && empty($data['reason'])) {
            return back()->withErrors(['reason' => 'Say why the card is being replaced, e.g. lost or damaged.']);
        }
        $card = $this->cards->issue($student, $request->user(), $data['reason'] ?? null);

        return back()->with('success', $hadOne ? "New card {$card->card_no} made. The old card no longer scans." : "Card {$card->card_no} made.");
    }

    public function revoke(Request $request, StudentIdCard $card): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:200']);
        $this->cards->revoke($card, $data['reason']);

        return back()->with('success', "Card {$card->card_no} stopped. It no longer scans.");
    }

    /**
     * Print cards as a PDF: chosen students, or whole classes. Students without a card get one first.
     * layout "sheet" = A4 pages to cut out (fronts, then backs mirrored for double-sided printing);
     * layout "single" = one card-sized page per side, for PVC card printers.
     */
    public function print(Request $request): HttpResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'student_ids' => 'array',
            'student_ids.*' => 'integer',
            'class_ids' => 'array',
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'design_id' => ['nullable', Rule::exists('id_card_designs', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'layout' => 'nullable|in:sheet,single',
            'not_printed' => 'boolean',
        ]);
        if (empty($data['student_ids']) && empty($data['class_ids'])) {
            return ReportCardController::problemPage('Choose some students or at least one class to print.');
        }
        $students = Student::where('status', 'active')
            ->when($data['student_ids'] ?? [], fn ($q, $ids) => $q->whereIn('id', $ids))
            ->when($data['class_ids'] ?? [], fn ($q, $ids) => $q->whereIn('class_id', $ids))
            ->orderBy('class_id')->orderBy('first_name')->orderBy('last_name')->get();
        if ($students->count() > self::MAX_PER_PRINT) {
            return ReportCardController::problemPage('That is '.$students->count().' cards. Print at most '.self::MAX_PER_PRINT.' at a time, for example one or two classes at once.');
        }
        $this->cards->issueMissing($students, $request->user());
        $cards = StudentIdCard::with('student')->whereIn('student_id', $students->pluck('id'))->where('status', 'active')
            ->when($data['not_printed'] ?? false, fn ($q) => $q->where('prints', 0))
            ->get()->sortBy(fn ($c) => $students->search(fn ($s) => $s->id === $c->student_id))->values();
        if ($cards->isEmpty()) {
            return ReportCardController::problemPage('There are no cards left to print for that choice.');
        }
        $design = isset($data['design_id']) ? IdCardDesign::find($data['design_id']) : IdCardDesign::forSchool($sid);
        $bytes = $this->pdf($design, $cards, $data['layout'] ?? 'sheet');
        StudentIdCard::whereIn('id', $cards->pluck('id'))->increment('prints', 1, ['printed_at' => now()]);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="id-cards.pdf"']);
    }

    /** One student's card as a card-sized PDF (front and back) */
    public function studentCard(Request $request, Student $student): HttpResponse
    {
        $card = StudentIdCard::with('student')->where('student_id', $student->id)->where('status', 'active')->latest('id')->first();
        if (! $card) {
            return ReportCardController::problemPage('This student has no card yet. Use "Make card" first.');
        }
        $design = IdCardDesign::forSchool($student->school_id);
        $bytes = $this->pdf($design, collect([$card]), $request->query('layout') === 'sheet' ? 'sheet' : 'single');
        $card->increment('prints', 1, ['printed_at' => now()]);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="id-card-'.$student->admission_no.'.pdf"']);
    }

    /* ---------- Designs ---------- */

    public function designs(Request $request): Response
    {
        IdCardDesign::forSchool($this->getSchoolId());

        return Inertia::render('SchoolAdmin/IdCards/Designs', [
            'designs' => IdCardDesign::orderByDesc('is_default')->orderBy('name')->get()->map(fn (IdCardDesign $d) => [
                'id' => $d->id, 'name' => $d->name, 'orientation' => $d->orientation, 'template' => $d->template,
                'primary_color' => $d->primary_color, 'accent_color' => $d->accent_color, 'title' => $d->title,
                'show' => $d->switches(), 'back_text' => $d->back_text, 'signer_id' => $d->signer_id,
                'valid_months' => $d->valid_months, 'is_default' => $d->is_default,
            ]),
            'signers' => ReportCardSigner::get(['id', 'label', 'name', 'signature_path'])->unique(fn ($s) => mb_strtolower($s->label.'|'.$s->name))->values()
                ->map(fn ($s) => ['id' => $s->id, 'label' => $s->name ? "{$s->label} ({$s->name})" : $s->label, 'has_signature' => (bool) $s->signature_path]),
            'switches' => array_keys(IdCardDesign::SHOW),
            'can' => ['edit' => $request->user()->can('settings.edit')],
        ]);
    }

    public function saveDesign(Request $request, ?IdCardDesign $design = null): RedirectResponse
    {
        $data = $this->designInput($request);
        if ($design) {
            $design->update($data);
        } else {
            $design = IdCardDesign::create($data + ['school_id' => $this->getSchoolId()]);
        }
        if ($design->is_default) {
            IdCardDesign::whereKeyNot($design->id)->update(['is_default' => false]);
        }

        return back()->with('success', 'Card design saved.');
    }

    public function deleteDesign(IdCardDesign $design): RedirectResponse
    {
        if (IdCardDesign::count() <= 1) {
            return back()->withErrors(['design' => 'Keep at least one design.']);
        }
        $design->delete();
        if (! IdCardDesign::where('is_default', true)->exists()) {
            IdCardDesign::orderBy('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Design removed.');
    }

    /** A made-up student's card with what is on the design form right now. Nothing is saved. */
    public function tryDesign(Request $request): HttpResponse
    {
        $check = validator($request->all(), $this->designRules() + ['school_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:10240', 'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:10240']);
        if ($check->fails()) {
            return ReportCardController::problemPage($check->errors()->first());
        }
        $sid = $this->getSchoolId();
        $design = new IdCardDesign(['school_id' => $sid] + $this->clean($check->validated()));
        $view = $this->cards->pdfData($design, collect([$this->cards->sample($sid)]), 'single');
        if ($logo = EmbeddedImage::fromUpload($request->file('school_logo'))) {
            $view['school']['logo'] = $logo;
        }
        if ($photo = EmbeddedImage::fromUpload($request->file('photo'))) {
            $view['cards'][0]['photo'] = $photo;
        }
        $html = view('id-cards.cards', $view + ['screen' => true])->render();

        return ReportCardController::screenPage($html, 'Preview: ID card', false, $design->orientation === 'landscape' ? 360 : 250);
    }

    /* ---------- Scanning ---------- */

    public function scanPage(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('SchoolAdmin/IdCards/Scan', [
            'code' => $request->query('code'),
            'can' => ['attendance' => $user->can('attendance.mark'), 'fees' => $user->can('fees.view')],
        ]);
    }

    /** What a scanned (or typed) card shows; with "mark", today's attendance is recorded too */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:300', 'mark' => 'nullable|in:present,late']);
        $card = IdCardService::find($data['code']);
        if (! $card || ! $card->student) {
            return response()->json(['found' => false, 'message' => 'No card of this school matches that code.'], 404);
        }
        $result = $this->cards->scan($card, $request->user());
        $result['found'] = true;
        $result['marked'] = null;
        if (! empty($data['mark'])) {
            if (! $request->user()->can('attendance.mark')) {
                return response()->json(['found' => true, 'message' => 'You cannot mark attendance.'] + $result, 403);
            }
            if ($card->state() !== 'valid') {
                $result['marked'] = 'refused';
            } elseif ($card->student->status !== 'active') {
                $result['marked'] = 'refused';
            } else {
                $result['marked'] = $this->cards->markAttendance($card->student, $data['mark']);
                $result['attendance'] = $result['attendance'] ?? $data['mark'];
            }
        }

        return response()->json($result);
    }

    /* ---------- helpers ---------- */

    private function pdf(IdCardDesign $design, Collection $cards, string $layout): string
    {
        $view = $this->cards->pdfData($design, $cards, $layout);
        $paper = $layout === 'single'
            ? [0, 0, ($design->orientation === 'landscape' ? 85.6 : 54) * 2.8346, ($design->orientation === 'landscape' ? 54 : 85.6) * 2.8346]
            : 'a4';

        return Pdf::loadView('id-cards.cards', $view)->setPaper($paper, 'portrait')->output();
    }

    private function designList()
    {
        IdCardDesign::forSchool($this->getSchoolId());

        return IdCardDesign::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'orientation', 'is_default']);
    }

    private function designRules(): array
    {
        return [
            'name' => 'required|string|max:60',
            'orientation' => ['required', Rule::in(IdCardDesign::ORIENTATIONS)],
            'template' => ['required', Rule::in(IdCardDesign::TEMPLATES)],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'title' => 'required|string|max:60',
            'show' => 'array',
            'show.*' => 'boolean',
            'back_text' => 'nullable|string|max:600',
            'signer_id' => ['nullable', Rule::exists('report_card_signers', 'id')->where('school_id', $this->getSchoolId())],
            'valid_months' => 'nullable|integer|between:1,72',
            'is_default' => 'boolean',
        ];
    }

    private function designInput(Request $request): array
    {
        return $this->clean($request->validate($this->designRules()));
    }

    private function clean(array $data): array
    {
        $data['show'] = collect(IdCardDesign::SHOW)->mapWithKeys(fn ($on, $key) => [$key => (bool) ($data['show'][$key] ?? $on)])->all();
        unset($data['school_logo'], $data['photo']);

        return $data;
    }
}
