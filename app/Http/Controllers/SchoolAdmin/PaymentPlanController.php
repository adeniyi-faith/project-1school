<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Services\InstalmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Saved ways of paying a fee in parts, and who is behind on their parts */
class PaymentPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = PaymentPlan::orderBy('name')->get();
        $using = Invoice::whereIn('payment_plan_id', $plans->pluck('id'))->whereIn('status', ['unpaid', 'partial'])
            ->pluck('payment_plan_id')->countBy();

        return Inertia::render('SchoolAdmin/Fees/PaymentPlans', [
            'plans' => $plans->map(fn (PaymentPlan $p) => [
                'id' => $p->id, 'name' => $p->name, 'description' => $p->description, 'parts' => $p->parts,
                'is_active' => $p->is_active, 'open_invoices' => (int) ($using[$p->id] ?? 0),
            ]),
            'can' => ['manage' => $request->user()->can('fees.structure')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentPlan::create($this->validated($request) + ['school_id' => $this->getSchoolId()]);

        return back()->with('success', 'Payment plan saved.');
    }

    public function update(Request $request, PaymentPlan $plan): RedirectResponse
    {
        // Invoices that already have parts keep them; the change is for the next time the plan is used
        $plan->update($this->validated($request));

        return back()->with('success', 'Payment plan updated. Invoices already on it keep their parts.');
    }

    public function destroy(PaymentPlan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('success', 'Payment plan removed. Invoices already on it keep their parts.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'parts' => 'required|array',
            'is_active' => 'boolean',
        ]);
        $data['parts'] = InstalmentService::cleanParts($data['parts'], withDates: false);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
