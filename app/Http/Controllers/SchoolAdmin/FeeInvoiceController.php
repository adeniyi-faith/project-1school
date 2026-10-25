<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceInstalment;
use App\Models\LedgerEntry;
use App\Models\PaymentPlan;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\FeeLedgerService;
use App\Services\InstalmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Invoices and their ledger: bill a class, take payments, add fines, reverse mistakes. */
class FeeInvoiceController extends Controller
{
    public function __construct(private FeeLedgerService $ledger, private InstalmentService $instalments) {}

    public function index(Request $request): Response
    {
        $sid = $this->getSchoolId();

        $invoices = Invoice::with([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'feeStructure:id,fee_category_id,class_id',
            'feeStructure.feeCategory:id,name',
            'instalments',
        ])
            ->when($request->status === 'behind', fn ($q) => $q->whereIn('status', ['unpaid', 'partial'])->whereHas('instalments', fn ($p) => $this->behind($p)))
            ->when($request->status === 'open', fn ($q) => $q->whereIn('status', ['unpaid', 'partial']))
            ->when($request->status && ! in_array($request->status, ['open', 'behind'], true), fn ($q) => $q->where('status', $request->status))
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
        $late = InvoiceInstalment::whereHas('invoice', fn ($q) => $q->whereIn('status', ['unpaid', 'partial']))->where(fn ($q) => $this->behind($q));

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
                'behind_count' => (clone $late)->distinct()->count('invoice_id'),
                'overdue' => round((float) (clone $late)->sum('amount') - (float) (clone $late)->sum('paid'), 2),
            ],
            'classes' => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'terms' => $this->terms(),
            'structures' => FeeStructure::with(['feeCategory:id,name', 'schoolClass:id,name'])->where('is_active', true)->get()
                ->map(fn (FeeStructure $f) => [
                    'id' => $f->id, 'class_id' => $f->class_id, 'class_name' => $f->schoolClass?->name,
                    'name' => $f->feeCategory?->name ?? 'Fee', 'amount' => (float) $f->amount,
                    'academic_year' => $f->academic_year, 'due_date' => $f->due_date?->toDateString(),
                ]),
            'plans' => $this->plans(),
            'filters' => $request->only('status', 'class_id', 'term_id', 'search'),
            'can' => ['issue' => $request->user()->can('fees.structure'), 'collect' => $request->user()->can('fees.collect')],
        ]);
    }

    /** "Take a payment" box: students matching a name or admission number, each with what they still owe */
    public function find(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $students = Student::with(['schoolClass:id,name', 'invoices' => fn ($i) => $i
            ->whereIn('status', ['unpaid', 'partial'])->with('feeStructure.feeCategory:id,name')->orderBy('id')])
            ->whereHas('invoices', fn ($i) => $i->whereIn('status', ['unpaid', 'partial']))
            ->where(fn ($w) => $w->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('admission_no', 'like', "%{$q}%"))
            ->orderBy('first_name')->limit(10)->get();

        return response()->json(['data' => $students->map(fn (Student $s) => [
            'id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no, 'class' => $s->schoolClass?->name,
            'invoices' => $s->invoices->map(fn (Invoice $i) => [
                'id' => $i->id, 'invoice_no' => $i->invoice_no, 'fee' => $i->feeStructure?->feeCategory?->name ?? 'Fee',
                'period' => $i->period, 'balance' => (float) $i->balance,
            ])->values(),
        ])->values()]);
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
            'instalments',
            'paymentPlan:id,name',
            'term:id,start_date,end_date',
        ]);
        $user = $request->user();

        return Inertia::render('SchoolAdmin/Fees/Invoice', [
            'invoice' => $this->row($invoice) + [
                'due_date' => $invoice->due_date?->toDateString(),
                'void_reason' => $invoice->void_reason,
                'guardian' => $invoice->student?->guardian?->only('name', 'phone'),
                'net_paid' => $this->ledger->netPaid($invoice),
                'plan_name' => $invoice->paymentPlan?->name,
                'term_dates' => $invoice->term ? ['start' => $invoice->term->start_date?->toDateString(), 'end' => $invoice->term->end_date?->toDateString()] : null,
            ],
            'plans' => $this->plans(),
            'entries' => $invoice->entries->map(fn (LedgerEntry $e) => [
                'id' => $e->id, 'type' => $e->type, 'amount' => $e->amount, 'method' => $e->method,
                'reference' => $e->reference, 'entry_date' => $e->entry_date?->toDateString(), 'note' => $e->note,
                'recorded_by' => $e->recorder?->name, 'reverses_id' => $e->reverses_id,
                'reversed' => $e->reversedBy !== null,
            ]),
            'can' => [
                'collect' => $user->can('fees.collect') && $invoice->isOpen(),
                'correct' => $user->can('fees.waiver') && $invoice->status !== 'void',
                'plan' => $user->can('fees.structure') && $invoice->isOpen(),
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
        [$parts, $plan] = $this->planInput($request);

        $term = isset($data['term_id']) ? Term::with('academicYear:id,name')->find($data['term_id']) : null;
        $period = trim($data['period'] ?? '') ?: ($term ? trim(($term->academicYear?->name ?? '') . ' · ' . $term->name, ' ·') : null);
        if (! $period) {
            return back()->withErrors(['period' => 'Choose a term or type the period this bill is for.']);
        }

        $made = 0;
        foreach (FeeStructure::with('feeCategory')->whereIn('id', $data['fee_structure_ids'])->get() as $structure) {
            $made += $this->ledger->issueInvoices($structure, $period, $term, $data['due_date'] ?? null, $request->user());
        }
        // Parts go on the new invoices (and any older open ones for this period that have none yet)
        $planned = $parts ? $this->instalments->applyToMany($this->openFor($data['fee_structure_ids'], $period), $parts, $plan, replace: false) : 0;

        return back()->with('success', ($made === 0
            ? 'No new invoices: every student already has these for this period.'
            : "{$made} invoice" . ($made === 1 ? '' : 's') . " created for {$period}.")
            . ($planned ? " {$planned} set to be paid in parts." : ''));
    }

    /** Put a payment plan on the open invoices of some fees for one period, e.g. after they were made */
    public function planMany(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'fee_structure_ids'   => 'required|array|min:1',
            'fee_structure_ids.*' => ['integer', Rule::exists('fee_structures', 'id')->where('school_id', $sid)],
            'term_id'             => ['required', Rule::exists('terms', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'replace'             => 'boolean',
        ]);
        [$parts, $plan] = $this->planInput($request, required: true);
        $term = Term::find($data['term_id']);

        $done = $this->instalments->applyToMany(
            $this->openFor($data['fee_structure_ids'], null)->where('term_id', $term->id)->values(), $parts, $plan, (bool) ($data['replace'] ?? false),
        );

        return back()->with('success', $done === 0
            ? 'Nothing changed: no open invoices for those fees and term' . (empty($data['replace']) ? ' without parts already.' : '.')
            : "{$done} invoice" . ($done === 1 ? '' : 's') . ' set to be paid in parts.');
    }

    /** Give one invoice its own parts, e.g. an arrangement agreed with a parent */
    public function plan(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isOpen()) {
            return back()->withErrors(['parts' => 'This invoice has nothing left to pay.']);
        }
        [$parts, $plan] = $this->planInput($request, required: true);
        $this->instalments->apply($invoice, $parts, $plan);

        return back()->with('success', 'This invoice will be paid in ' . count($parts) . ' parts.');
    }

    public function unplan(Invoice $invoice): RedirectResponse
    {
        $this->instalments->remove($invoice);

        return back()->with('success', 'Parts removed. The whole amount is due by ' . ($invoice->due_date?->format('j M Y') ?? 'the due date') . '.');
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
            'instalments' => $i->relationLoaded('instalments') ? InstalmentService::summary($i) : null,
        ];
    }

    /** Parts that were due before today and are not fully paid */
    private function behind($query)
    {
        return $query->whereDate('due_date', '<', now()->toDateString())->whereColumn('paid', '<', 'amount');
    }

    /** The dated parts sent with a request, and the saved plan they came from, if any */
    private function planInput(Request $request, bool $required = false): array
    {
        $data = $request->validate([
            'parts' => [$required ? 'required' : 'nullable', 'array'],
            'payment_plan_id' => ['nullable', Rule::exists('payment_plans', 'id')->where('school_id', $this->getSchoolId())],
        ]);
        if (empty($data['parts'])) {
            return [null, null];
        }

        return [InstalmentService::cleanParts($data['parts']), isset($data['payment_plan_id']) ? PaymentPlan::find($data['payment_plan_id']) : null];
    }

    private function openFor(array $structureIds, ?string $period)
    {
        return Invoice::whereIn('fee_structure_id', $structureIds)->whereIn('status', ['unpaid', 'partial'])
            ->when($period, fn ($q) => $q->where('period', $period))->get();
    }

    private function plans()
    {
        return PaymentPlan::where('is_active', true)->orderBy('name')->get(['id', 'name', 'parts']);
    }

    private function terms()
    {
        return Term::with('academicYear:id,name,start_date')->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $b->academicYear?->start_date, (string) $a->academicYear?->start_date), ['sequence', 'asc']])
            ->map(fn (Term $t) => [
                'id' => $t->id, 'label' => trim(($t->academicYear?->name ?? '') . ' · ' . $t->name, ' ·'), 'is_current' => $t->is_current,
                'start' => $t->start_date?->toDateString(), 'end' => $t->end_date?->toDateString(),
            ])
            ->values();
    }
}
