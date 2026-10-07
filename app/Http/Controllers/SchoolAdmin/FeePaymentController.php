<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

    public function create(Request $request)
    {
        $sid = $this->getSchoolId();

        $student = null;
        $structures = collect();

        if ($request->student_id) {
            $student = Student::with('schoolClass:id,name')->findOrFail($request->student_id);
            $structures = FeeStructure::with('feeCategory:id,name,type')
                ->where('school_id', $sid)
                ->where('class_id', $student->class_id)
                ->where('is_active', true)
                ->get();
        }

        return Inertia::render('SchoolAdmin/Fees/Collect', [
            'student'    => $student,
            'structures' => $structures,
            'classes'    => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'amount_due'       => 'required|numeric|min:0',
            'amount_paid'      => 'required|numeric|min:0',
            'discount'         => 'nullable|numeric|min:0',
            'fine'             => 'nullable|numeric|min:0',
            'payment_date'     => 'required|date',
            'month_year'       => 'nullable|string|max:10',
            'method'           => 'required|in:cash,card,online,bkash,nagad,rocket',
            'note'             => 'nullable|string|max:500',
        ]);

        $sid = $this->getSchoolId();

        $amountDue    = (float) $data['amount_due'];
        $amountPaid   = (float) $data['amount_paid'];
        $discount     = (float) ($data['discount'] ?? 0);
        $fine         = (float) ($data['fine'] ?? 0);
        $netDue       = $amountDue + $fine - $discount;
        $balance      = $netDue - $amountPaid;

        $status = match (true) {
            $balance <= 0          => 'paid',
            $amountPaid > 0        => 'partial',
            default                => 'pending',
        };

        FeePayment::create(array_merge($data, [
            'school_id' => $sid,
            'discount'  => $discount,
            'fine'      => $fine,
            'status'    => $status,
        ]));

        return redirect('/school/fees/payments')->with('success', 'Payment recorded successfully.');
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

    public function outstanding(Request $request)
    {
        $sid = $this->getSchoolId();

        // Students with pending/overdue/partial fees
        $query = FeePayment::with([
            'student:id,first_name,last_name,admission_no,class_id',
            'student.schoolClass:id,name',
            'feeStructure:id,fee_category_id,academic_year',
            'feeStructure.feeCategory:id,name',
        ])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->when($request->class_id, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_id', $request->class_id)))
            ->orderBy('payment_date');

        $outstandingPayments = $query->get();

        // Group by student
        $byStudent = $outstandingPayments->groupBy('student_id')->map(function ($payments) {
            $student = $payments->first()->student;
            $totalDue  = $payments->sum(fn ($p) => (float)$p->amount_due + (float)$p->fine - (float)$p->discount);
            $totalPaid = $payments->sum('amount_paid');
            $balance   = $totalDue - $totalPaid;

            return [
                'student'       => $student,
                'payment_count' => $payments->count(),
                'total_due'     => round($totalDue, 2),
                'total_paid'    => round($totalPaid, 2),
                'balance'       => round($balance, 2),
                'payments'      => $payments->values(),
            ];
        })->values();

        return Inertia::render('SchoolAdmin/Fees/Outstanding', [
            'outstanding' => $byStudent,
            'classes'     => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'filters'     => $request->only('class_id'),
            'summary'     => [
                'total_students'    => $byStudent->count(),
                'total_outstanding' => round($byStudent->sum('balance'), 2),
            ],
        ]);
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
