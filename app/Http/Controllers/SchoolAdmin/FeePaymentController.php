<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The old fee payment records (before invoices). Kept read-only so past receipts can still be
 * looked up; their money was copied into invoices and the ledger.
 */
class FeePaymentController extends Controller
{
    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $payments = FeePayment::with([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'feeStructure:id,fee_category_id,academic_year,frequency',
            'feeStructure.feeCategory:id,name,type',
        ])
            ->when($request->student_id,  fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->status,      fn ($q) => $q->where('status', $request->status))
            ->when($request->class_id,    fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_id', $request->class_id)))
            ->when($request->month_year,  fn ($q) => $q->where('month_year', $request->month_year))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Fees/Payments', [
            'payments'  => $payments,
            'classes'   => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'filters'   => $request->only('student_id', 'status', 'class_id', 'month_year'),
            'stats'     => $this->getStats($sid),
        ]);
    }

    /**
     * The old "Collect fee" form. Fees are now taken on invoices, and the old rows were copied there,
     * so this sends staff to the invoices instead of adding to the old table.
     */
    public function create(Request $request)
    {
        return redirect()->route('school.fees.invoices.index', array_filter(['search' => $request->query('student_id')]))
            ->with('info', 'Fees are now taken on invoices. Open the student\'s invoice and press "Record payment".');
    }

    public function store()
    {
        return redirect()->route('school.fees.invoices.index')
            ->with('error', 'Fees are now taken on invoices. Open the student\'s invoice and press "Record payment".');
    }

    public function show(FeePayment $feePayment)
    {
        $feePayment->load([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'feeStructure.feeCategory:id,name,type',
        ]);

        return Inertia::render('SchoolAdmin/Fees/Receipt', [
            'payment' => $feePayment,
        ]);
    }

    /** Who still owes now comes from the invoices. */
    public function outstanding()
    {
        return redirect()->route('school.fees.invoices.index', ['status' => 'open']);
    }

    private function getStats(int $sid): array
    {
        $base = FeePayment::where('school_id', $sid);

        return [
            'total_collected'   => (float) (clone $base)->whereIn('status', ['paid', 'partial'])->sum('amount_paid'),
            'total_outstanding' => (float) (clone $base)->whereIn('status', ['pending', 'partial', 'overdue'])
                ->selectRaw('SUM(amount_due + fine - discount - amount_paid) as bal')->value('bal'),
            'paid_count'        => (clone $base)->where('status', 'paid')->count(),
            'pending_count'     => (clone $base)->whereIn('status', ['pending', 'overdue'])->count(),
        ];
    }
}
