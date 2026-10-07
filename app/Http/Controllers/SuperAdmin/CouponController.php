<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SuperAdmin/Coupons/Index', [
            'coupons' => Coupon::withTrashed(false)->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'        => 'required|string|max:50|unique:coupons,code',
            'type'        => 'required|in:percent,fixed',
            'value'       => 'required|numeric|min:0',
            'expires_at'  => 'nullable|date',
            'max_uses'    => 'required|integer|min:0',
            'is_active'   => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        Coupon::create($data);
        return back()->with('success', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'type'        => 'required|in:percent,fixed',
            'value'       => 'required|numeric|min:0',
            'expires_at'  => 'nullable|date',
            'max_uses'    => 'required|integer|min:0',
            'is_active'   => 'boolean',
            'description' => 'nullable|string|max:255',
        ]);

        $coupon->update($data);
        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }
}
