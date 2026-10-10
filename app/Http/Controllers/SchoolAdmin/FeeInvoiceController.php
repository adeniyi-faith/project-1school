<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\FeeLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Invoices and their ledger: bill a class, take payments, add fines, reverse mistakes. */
class FeeInvoiceController extends Controller
{
    public function __construct(private FeeLedgerService $ledger) {}

    public function index(Request $request): Response
    {
        $sid = $this->getSchoolId();

        $invoices = Invoice::with([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'feeStructure:id,fee_category_id,class_id',
            'feeStructure.feeCategory:id,name',
        ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->class_id, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_id', $request->class_id)))
            ->when($request->term_id, fn ($q) => $q->where('term_id', $request->term_id))
            ->when($request->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_no', 'like', '%' . $request->search . '%')
                ->orWhereHas('student', fn ($s) => $s->where('admission_no', 'like', '%' . $request->search . '%')
                    ->orWhere('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%'))))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $open = Invoice::whereIn('status', ['unpaid', 'partial']);

        return Inertia::render('SchoolAdmin/Fees/Invoices', [
            'invoices' => [
                'data' => collect($invoices->items())->map(fn (Invoice $i) => $this->row($i)),
                'meta' => [
                    'total' => $invoices->total(), 'current_page' => $invoices->currentPage(), 'last_page' => $invoices->lastPage(),
                    'from' => $invoices->firstItem(), 'to' => $invoices->lastItem(),
                ],
                'links' => ['prev' => $invoices->previousPageUrl(), 'next' => $invoices->nextPageUrl()],
            ],
            'stats' => [
                'outstanding' => round((float) (clone $open)->sum('balance'), 2),
                'open_count' => (clone $open)->count(),
                'collected' => round(-(float) LedgerEntry::where('type', 'payment')->sum('amount')
                    - (float) LedgerEntry::where('type', 'reversal')->whereHas('reverses', fn ($q) => $q->where('type', 'payment'))->sum('amount'), 2),
            ],
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'terms' => $this->terms(),
            'structures' => FeeStructure::with(['feeCategory:id,name', 'schoolClass:id,name'])->where('is_active', true)->get()
                ->map(fn (FeeStructure $f) => [
                    'id' => $f->id, 'class_id' => $f->class_id, 'class_name' => $f->schoolClass?->name,
                    'name' => $f->feeCategory?->name ?? 'Fee', 'amount' => (float) $f->amount,
                    'academic_year' => $f->academic_year, 'due_date' => $f->due_date?->toDateString(),
                ]),
            'filters' => $request->only('status', 'class_id', 'term_id', 'search'),
            'can' => ['issue' => $request->user()->can('fees.structure')],
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $invoice->load([
            'student:id,first_name,last_name,admission_no,class_id,guardian_id',
            'student.schoolClass:id,name',
            'student.guardian:id,name,phone',
            'feeStructure.feeCategory:id,name',
            'entries.recorder:id,name',
            'entries.reversedBy:id,reverses_id',
        ]);
        $user = $request->user();

        return Inertia::render('SchoolAdmin/Fees/Invoice', [
            'invoice' => $this->row($invoice) + [
                'due_date' => $invoice->due_date?->toDateString(),
                'void_reason' => $invoice->void_reason,
                'guardian' => $invoice->student?->guardian?->only('name', 'phone'),
                'net_paid' => $this->ledger->netPaid($invoice),
            ],
            'entries' => $invoice->entries->map(fn (LedgerEntry $e) => [
                'id' => $e->id, 'type' => $e->type, 'amount' => $e->amount, 'method' => $e->method,
                'reference' => $e->reference, 'entry_date' => $e->entry_date?->toDateString(), 'note' => $e->note,
                'recorded_by' => $e->recorder?->name, 'reverses_id' => $e->reverses_id,
                'reversed' => $e->reversedBy !== null,
            ]),
            'can' => [
                'collect' => $user->can('fees.collect') && $invoice->isOpen(),
                'correct' => $user->can('fees.waiver') && $invoice->status !== 'void',
            ],
        ]);
    }

    /** Bill every active student in a class for one fee and one period (usually a term). */
    public function issue(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'fee_structure_ids'   => 'required|array|min:1',
            'fee_structure_ids.*' => ['integer', Rule::exists('fee_structures', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'term_id'             => ['nullable', Rule::exists('terms', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'period'              => 'nullable|string|max:60',
            'due_date'            => 'nullable|date',
        ]);

        $term = isset($data['term_id']) ? Term::with('academicYear:id,name')->find($data['term_id']) : null;
        $period = trim($data['period'] ?? '') ?: ($term ? trim(($term->academicYear?->name ?? '') . ' · ' . $term->name, ' ·') : null);
        if (! $period) {
            return back()->withErrors(['period' => 'Choose a term or type the period this bill is for.']);
        }

        $made = 0;
        foreach (FeeStructure::with('feeCategory')->whereIn('id', $data['fee_structure_ids'])->get() as $structure) {
            $made += $this->ledger->issueInvoices($structure, $period, $term, $data['due_date'] ?? null, $request->user());
        }

        return back()->with('success', $made === 0
            ? 'No new invoices: every student already has these for this period.'
            : "{$made} invoice" . ($made === 1 ? '' : 's') . " created for {$period}.");
    }

    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'method'     => 'required|in:cash,bank_transfer,pos,card,online,ussd',
            'entry_date' => 'required|date|before_or_equal:today',
            'note'       => 'nullable|string|max:500',
        ]);

        $entry = $this->ledger->recordPayment($invoice, (float) $data['amount'], $data['method'], $data['entry_date'], null, $data['note'] ?? null, $request->user());

        return back()->with('success', 'Payment of ₦' . number_format(-$entry->amount, 2) . " recorded. Receipt {$entry->reference}.");
    }

    public function fine(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'required|string|max:500',
        ]);

        $this->ledger->addFine($invoice, (float) $data['amount'], $data['note'], $request->user());

        return back()->with('success', 'Fine added.');
    }

    public function reverse(Request $request, Invoice $invoice, LedgerEntry $entry): RedirectResponse
    {
        abort_unless($entry->invoice_id === $invoice->id, 404);
        $data = $request->validate(['reason' => 'required|string|max:255']);

        $this->ledger->reverse($entry, $data['reason'], $request->user());

        return back()->with('success', 'Line reversed. The original stays in the record.');
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);

        $this->ledger->void($invoice, $data['reason'], $request->user());

        return back()->with('success', 'Invoice cancelled.');
    }

    private function row(Invoice $i): array
    {
        return [
            'id' => $i->id,
            'invoice_no' => $i->invoice_no,
            'student' => $i->student ? [
                'id' => $i->student->id, 'name' => $i->student->full_name, 'admission_no' => $i->student->admission_no,
                'class' => $i->student->schoolClass?->name,
            ] : null,
            'fee' => $i->feeStructure?->feeCategory?->name ?? 'Fee',
            'period' => $i->period,
            'amount' => $i->amount,
            'balance' => $i->balance,
            'status' => $i->status,
            'due_date' => $i->due_date?->toDateString(),
        ];
    }

    private function terms()
    {
        return Term::with('academicYear:id,name,start_date')->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $b->academicYear?->start_date, (string) $a->academicYear?->start_date), ['sequence', 'asc']])
            ->map(fn (Term $t) => ['id' => $t->id, 'label' => trim(($t->academicYear?->name ?? '') . ' · ' . $t->name, ' ·'), 'is_current' => $t->is_current])
            ->values();
    }
}
