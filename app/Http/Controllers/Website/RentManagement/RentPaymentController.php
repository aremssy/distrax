<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\RentPayment;
use App\Models\RentReceipt;
use App\Models\Tenancy;
use App\Services\PostEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RentPaymentController extends Controller
{
    use GatesRentManagementFeatures;

    public function store(Request $request, Tenancy $tenancy): RedirectResponse
    {
        $this->authorize('update', $tenancy);

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $tenancy->rentPayments()->create([...$validated, 'status' => 'pending']);

        return back()->with('success', 'Rent payment added.');
    }

    public function markPaid(Request $request, RentPayment $rentPayment, PostEntitlementService $entitlement): RedirectResponse
    {
        $this->authorize('update', $rentPayment->tenancy);

        if ($rentPayment->status === 'paid') {
            return back()->withErrors(['limit' => 'This rent payment has already been marked paid.']);
        }

        $rentPayment->update(['status' => 'paid', 'paid_at' => now()]);

        if ($this->hasFeature($entitlement->unlockedFeatures($request->user()), 'receipts')) {
            RentReceipt::create([
                'rent_payment_id' => $rentPayment->id,
                'tenancy_id' => $rentPayment->tenancy_id,
                'receipt_number' => 'RCPT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'amount' => $rentPayment->amount,
                'issued_at' => now(),
            ]);
        }

        return back()->with('success', 'Rent payment marked paid.');
    }
}
