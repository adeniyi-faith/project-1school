<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $subscriptions = SchoolSubscription::with(['school', 'package', 'coupon'])
            ->when($request->school_id, fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->withTrashed(false)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // KPI
        $kpi = [
            'total'    => SchoolSubscription::count(),
            'active'   => SchoolSubscription::where('status', 'active')->count(),
            'trial'    => SchoolSubscription::where('status', 'trial')->count(),
            'expired'  => SchoolSubscription::where('status', 'expired')->count(),
        ];

        return Inertia::render('SuperAdmin/Subscriptions/Index', [
            'subscriptions' => [
                'data' => $subscriptions->items(),
                'meta' => [
                    'total'        => $subscriptions->total(),
                    'per_page'     => $subscriptions->perPage(),
                    'current_page' => $subscriptions->currentPage(),
                    'last_page'    => $subscriptions->lastPage(),
                ],
            ],
            'schools'  => School::select('id', 'name')->orderBy('name')->get(),
            'packages' => Package::where('is_active', true)->select('id', 'name', 'price_monthly', 'price_yearly')->get(),
            'coupons'  => Coupon::where('is_active', true)->select('id', 'code', 'type', 'value')->get(),
            'kpi'      => $kpi,
            'filters'  => $request->only(['school_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_id'      => 'required|integer|exists:schools,id',
            'package_id'     => 'required|integer|exists:packages,id',
            'coupon_id'      => 'nullable|integer|exists:coupons,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'status'         => 'required|in:trial,active,expired,suspended',
            'is_trial'       => 'boolean',
            'trial_ends_at'  => 'nullable|date',
            'amount_paid'    => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        SchoolSubscription::create($data);

        return back()->with('success', 'Subscription assigned.');
    }

    public function update(Request $request, SchoolSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'package_id'     => 'required|integer|exists:packages,id',
            'coupon_id'      => 'nullable|integer|exists:coupons,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'status'         => 'required|in:trial,active,expired,suspended',
            'is_trial'       => 'boolean',
            'trial_ends_at'  => 'nullable|date',
            'amount_paid'    => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        $subscription->update($data);

        return back()->with('success', 'Subscription updated.');
    }

    public function destroy(SchoolSubscription $subscription): RedirectResponse
    {
        $subscription->delete();
        return back()->with('success', 'Subscription removed.');
    }
}
