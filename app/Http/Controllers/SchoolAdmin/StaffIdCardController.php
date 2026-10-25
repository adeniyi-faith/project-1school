<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\IdCardDesign;
use App\Models\Staff;
use App\Models\StaffIdCard;
use App\Services\StaffIdCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Staff ID cards: the register, making cards, replacing a lost one and printing. Cards look like student
 * cards (same design) and scan on the same screen.
 */
class StaffIdCardController extends Controller
{
    public const MAX_PER_PRINT = 300;

    public function __construct(private StaffIdCardService $cards) {}

    public function index(Request $request): Response
    {
        $today = now()->toDateString();
        $valid = fn ($q) => $q->where('status', 'active')->where(fn ($w) => $w->whereNull('expires_on')->orWhereDate('expires_on', '>=', $today));
        $filter = $request->string('show')->toString();

        $staff = Staff::with(['department:id,name', 'designation:id,name', 'idCards' => fn ($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->when($filter === 'without', fn ($q) => $q->whereDoesntHave('idCards', $valid))
            ->when($filter === 'with', fn ($q) => $q->whereHas('idCards', $valid))
            ->when($filter === 'no_photo', fn ($q) => $q->whereNull('photo'))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('emp_id', 'like', "%{$s}%")))
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(50)->withQueryString();

        $active = Staff::where('status', 'active');

        return Inertia::render('SchoolAdmin/IdCards/Staff', [
            'staff' => [
                'data' => collect($staff->items())->map(function (Staff $s) {
                    $card = $s->idCards->first();

                    return [
                        'id' => $s->id, 'name' => $s->full_name, 'emp_id' => $s->emp_id,
                        'role' => trim(($s->designation?->name ?? '').(($s->designation && $s->department) ? ', ' : '').($s->department?->name ?? '')),
                        'has_photo' => (bool) $s->photo, 'photo_url' => $s->photo_url,
                        'card' => $card ? ['id' => $card->id, 'card_no' => $card->card_no, 'state' => $card->state(), 'expires_on' => $card->expires_on?->toDateString(), 'prints' => $card->prints] : null,
                    ];
                }),
                'meta' => ['total' => $staff->total(), 'from' => $staff->firstItem(), 'to' => $staff->lastItem(), 'last_page' => $staff->lastPage()],
                'links' => ['prev' => $staff->previousPageUrl(), 'next' => $staff->nextPageUrl()],
            ],
            'stats' => [
                'staff' => (clone $active)->count(),
                'with_card' => (clone $active)->whereHas('idCards', $valid)->count(),
                'without_card' => (clone $active)->whereDoesntHave('idCards', $valid)->count(),
                'without_photo' => (clone $active)->whereNull('photo')->count(),
            ],
            'filters' => $request->only('show', 'search'),
            'max_per_print' => self::MAX_PER_PRINT,
        ]);
    }

    /** Cards for every active staff member who has no working card */
    public function issueMissing(Request $request): RedirectResponse
    {
        $made = $this->cards->issueMissing(Staff::where('status', 'active')->get(), $request->user());

        return back()->with('success', $made === 0 ? 'Everyone already has a working card.' : "{$made} new card".($made === 1 ? '' : 's').' made. Print them from the list.');
    }

    /** A new card for one staff member (first card, or replacing a lost or damaged one) */
    public function issue(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:200']);
        $hadOne = StaffIdCard::where('staff_id', $staff->id)->where('status', 'active')->exists();
        if ($hadOne && empty($data['reason'])) {
            return back()->withErrors(['reason' => 'Say why the card is being replaced, e.g. lost or damaged.']);
        }
        $card = $this->cards->issue($staff, $request->user(), $data['reason'] ?? null);

        return back()->with('success', $hadOne ? "New card {$card->card_no} made. The old card no longer scans." : "Card {$card->card_no} made.");
    }

    public function revoke(Request $request, StaffIdCard $card): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:200']);
        $this->cards->revoke($card, $data['reason']);

        return back()->with('success', "Card {$card->card_no} stopped. It no longer scans.");
    }

    /** Print working cards as a PDF: chosen staff, or everyone who has one. Staff without a card get one first. */
    public function print(Request $request): HttpResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'staff_ids' => 'array',
            'staff_ids.*' => 'integer',
            'layout' => 'nullable|in:sheet,single',
            'not_printed' => 'boolean',
        ]);
        $staff = Staff::where('status', 'active')
            ->when($data['staff_ids'] ?? [], fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('first_name')->orderBy('last_name')->get();
        if ($staff->count() > self::MAX_PER_PRINT) {
            return ReportCardController::problemPage('That is '.$staff->count().' cards. Print at most '.self::MAX_PER_PRINT.' at a time.');
        }
        $this->cards->issueMissing($staff, $request->user());
        $cards = StaffIdCard::with('staff')->whereIn('staff_id', $staff->pluck('id'))->where('status', 'active')
            ->when($data['not_printed'] ?? false, fn ($q) => $q->where('prints', 0))
            ->get()->sortBy(fn ($c) => $staff->search(fn ($s) => $s->id === $c->staff_id))->values();
        if ($cards->isEmpty()) {
            return ReportCardController::problemPage('There are no cards left to print for that choice.');
        }

        $design = IdCardDesign::forSchool($sid);
        $layout = $data['layout'] ?? 'sheet';
        $view = $this->cards->pdfData($design, $cards, $layout);
        $landscape = $design->orientation === 'landscape';
        $paper = $layout === 'single' ? [0, 0, ($landscape ? 85.6 : 54) * 2.8346, ($landscape ? 54 : 85.6) * 2.8346] : 'a4';
        $bytes = Pdf::loadView('id-cards.cards', $view)->setPaper($paper, 'portrait')->output();
        StaffIdCard::whereIn('id', $cards->pluck('id'))->increment('prints', 1, ['printed_at' => now()]);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="staff-id-cards.pdf"']);
    }
}
