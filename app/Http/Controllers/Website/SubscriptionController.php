<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\PurchasePackageRequest;
use App\Http\Requests\Website\StoreSubscriptionRequest;
use App\Models\Invoice;
use App\Models\ListingPackage;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Payment\GatewayFactory;
use App\Services\PlanPurchaseService;
use App\Services\PostEntitlementService;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function index(Request $request, WalletService $wallets, PostEntitlementService $entitlements, GatewayFactory $gateways): View
    {
        $this->flashCheckoutOutcome($request);

        $wallet = $wallets->findOrCreateForUser($request->user());
        $wallet->load(['transactions' => fn ($query) => $query->latest()->limit(20)]);
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();
        $packages = ListingPackage::where('is_active', true)->orderBy('sort_order')->get();
        $currentSubscription = $request->user()->subscriptions()->with('plan')->active()->first();
        $payments = Payment::whereBelongsTo($request->user())->latest()->limit(10)->get();
        $invoices = Invoice::whereBelongsTo($request->user())->latest('issued_at')->limit(10)->get();
        $entitlement = $entitlements->summary($request->user());
        $activeGateways = $gateways->activeGateways();

        return view('website.dashboard.wallet', compact('wallet', 'plans', 'packages', 'currentSubscription', 'payments', 'invoices', 'entitlement', 'activeGateways'));
    }

    /**
     * Translate a `?checkout=` marker left by a gateway browser callback into a
     * flash message so the shared SweetAlert flash handler surfaces it.
     */
    private function flashCheckoutOutcome(Request $request): void
    {
        match ($request->query('checkout')) {
            'success' => session()->now('success', 'Payment completed successfully.'),
            'failed' => session()->now('error', 'Your payment could not be completed.'),
            'cancelled' => session()->now('warning', 'Payment was cancelled.'),
            default => null,
        };
    }

    public function subscribe(StoreSubscriptionRequest $request, PlanPurchaseService $purchases): RedirectResponse
    {
        if ($request->user()->subscriptions()->active()->exists()) {
            return back()->withErrors(['plan' => 'Cancel your current plan before selecting another one.']);
        }

        $isWallet = $request->input('payment_method', 'gateway') === 'wallet';
        $gateway = $isWallet ? 'wallet' : (string) $request->input('gateway');

        try {
            $plan = SubscriptionPlan::active()->findOrFail($request->integer('plan_id'));
            $payment = $purchases->subscribe($request->user(), $plan, $gateway, $request->boolean('auto_renew'), $request->input('coupon_code'));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        if (! $isWallet) {
            return redirect()->route('checkout.start', $payment);
        }

        return back()->with('success', 'Subscription activated.');
    }

    public function purchasePackage(PurchasePackageRequest $request, PlanPurchaseService $purchases): RedirectResponse
    {
        $package = ListingPackage::where('is_active', true)->findOrFail($request->integer('package_id'));
        $isWallet = $request->input('payment_method') === 'wallet';
        $gateway = $isWallet ? 'wallet' : (string) $request->input('gateway');

        $boostListing = null;

        if ($request->filled('listing_id')) {
            $boostListing = PropertyListing::where('owner_id', $request->user()->id)
                ->findOrFail($request->integer('listing_id'));
        }

        try {
            $payment = $purchases->purchasePackage($request->user(), $package, $gateway, boostListing: $boostListing);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        if (! $isWallet) {
            return redirect()->route('checkout.start', $payment);
        }

        return back()->with('success', 'Listing package activated.');
    }

    public function cancel(Request $request, UserSubscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        abort_unless($subscription->isActive(), 422);
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now(), 'auto_renew' => false]);

        return back()->with('success', 'Subscription cancelled.');
    }
}
