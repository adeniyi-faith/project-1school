<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\Invoice;
use App\Models\Scholarship;
use App\Models\Student;
use App\Models\StudentScholarship;
use App\Services\FeeLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Named scholarships and discounts, and which students hold them. Giving one
 * records who approved it, and takes it off the student's open invoices.
 */
class ScholarshipController extends Controller
{
    public function __construct(private FeeLedgerService $ledger) {}

    public function index(Request $request): Response
    {
        return Inertia::render('SchoolAdmin/Fees/Scholarships', [
            'scholarships' => Scholarship::with('feeCategory:id,name')->withCount(['awards' => fn ($q) => $q->whereNull('revoked_at')])
                ->orderBy('name')->get()
                ->map(fn (Scholarship $s) => [
                    'id' => $s->id, 'name' => $s->name, 'type' => $s->type, 'value' => $s->value,
                    'fee_category_id' => $s->fee_category_id, 'fee_category' => $s->feeCategory?->name,
                    'description' => $s->description, 'is_active' => $s->is_active, 'holders' => $s->awards_count,
                ]),
            'awards' => StudentScholarship::with([
                'student:id,first_name,last_name,admission_no,class_id', 'student.schoolClass:id,name',
                'scholarship:id,name', 'approver:id,name',
            ])->latest('id')->get()
                ->map(fn (StudentScholarship $a) => [
                    'id' => $a->id,
                    'student' => $a->student ? ['name' => $a->student->full_name, 'admission_no' => $a->student->admission_no, 'class' => $a->student->schoolClass?->name] : null,
                    'scholarship' => $a->scholarship?->name,
                    'approved_by' => $a->approver?->name,
                    'approved_at' => $a->approved_at?->toDateString(),
                    'note' => $a->note,
                    'active' => $a->isActive(),
                ]),
            'categories' => FeeCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'can' => ['manage' => $request->user()->can('fees.waiver')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Scholarship::create($this->validated($request) + ['school_id' => $this->getSchoolId()]);

        return back()->with('success', 'Scholarship added.');
    }

    public function update(Request $request, Scholarship $scholarship): RedirectResponse
    {
        // Changing a policy affects future invoices only; discounts already given stay as they are
        $scholarship->update($this->validated($request));

        return back()->with('success', 'Scholarship updated. Invoices already made keep their discount.');
    }

    public function destroy(Scholarship $scholarship): RedirectResponse
    {
        $scholarship->update(['is_active' => false]);
        $scholarship->delete();

        return back()->with('success', 'Scholarship removed. Discounts already given stay on their invoices.');
    }

    /** Give a scholarship to a student (found by admission number) and apply it to their open invoices. */
    public function award(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'admission_no'   => 'required|string|max:50',
            'scholarship_id' => ['required', Rule::exists('scholarships', 'id')->where('school_id', $sid)->whereNull('deleted_at')->where('is_active', true)],
            'note'           => 'required|string|max:500',
        ], [
            'note.required' => 'Say why this student gets the scholarship, for the record.',
        ]);

        $student = Student::where('admission_no', trim($data['admission_no']))->first();
        if (! $student) {
            return back()->withErrors(['admission_no' => 'No student with that admission number in this school.']);
        }
        if (StudentScholarship::where('student_id', $student->id)->where('scholarship_id', $data['scholarship_id'])->whereNull('revoked_at')->exists()) {
            return back()->withErrors(['scholarship_id' => "{$student->full_name} already has this scholarship."]);
        }

        $applied = DB::transaction(function () use ($data, $student, $sid, $request) {
            $award = StudentScholarship::create([
                'school_id' => $sid, 'student_id' => $student->id, 'scholarship_id' => $data['scholarship_id'],
                'approved_by' => $request->user()->id, 'approved_at' => now(), 'note' => $data['note'],
            ]);
            activity('fees')->causedBy($request->user())->performedOn($award)
                ->withProperties(['student_id' => $student->id, 'scholarship_id' => $award->scholarship_id])
                ->log('Scholarship approved');

            return Invoice::with('feeStructure')->where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial'])->get()
                ->filter(fn (Invoice $invoice) => $this->ledger->applyScholarship($award->load('scholarship'), $invoice, $request->user()) !== null)
                ->count();
        });

        return back()->with('success', "Scholarship given to {$student->full_name}" . ($applied ? " and taken off {$applied} open invoice" . ($applied === 1 ? '' : 's') : '') . '.');
    }

    /** Stop a scholarship for future invoices. Discounts already given stay; reverse them on the invoice if needed. */
    public function revoke(Request $request, StudentScholarship $award): RedirectResponse
    {
        if (! $award->isActive()) {
            return back();
        }

        $award->forceFill(['revoked_by' => $request->user()->id, 'revoked_at' => now()])->save();
        activity('fees')->causedBy($request->user())->performedOn($award)->log('Scholarship revoked');

        return back()->with('success', 'Scholarship stopped. It will not apply to new invoices.');
    }

    private function validated(Request $request): array
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'type'            => 'required|in:percent,fixed',
            'value'           => 'required|numeric|min:0.01' . ($request->input('type') === 'percent' ? '|max:100' : ''),
            'fee_category_id' => ['nullable', Rule::exists('fee_categories', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'description'     => 'nullable|string|max:500',
            'is_active'       => 'boolean',
        ]);

        return $data + ['is_active' => true];
    }
}
