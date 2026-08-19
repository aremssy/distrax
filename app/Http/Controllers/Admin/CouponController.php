<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $coupons = Coupon::query()
            ->withCount('payments')
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where('code', 'like', "%{$search}%"))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(SaveCouponRequest $request): RedirectResponse
    {
        Coupon::create($request->validated());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function show(Coupon $coupon): RedirectResponse
    {
        return redirect()->route('admin.coupons.index');
    }

    public function update(SaveCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->validated());

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->payments()->exists()) {
            return redirect()->route('admin.coupons.index')->with('error', 'Coupons with payments cannot be deleted. Deactivate instead.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }
}
