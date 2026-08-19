<?php

namespace App\Http\Controllers\Api\V1\Package;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Package\BuyPackageRequest;
use App\Http\Resources\Api\V1\PackageResource;
use App\Models\ListingPackage;
use App\Models\Payment;
use App\Services\Payment\CheckoutUrlResolver;
use App\Services\PlanPurchaseService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

class PackageController extends ApiController
{
    /**
     * GET /api/v1/packages
     *
     * List all active listing packages (post-quota bundles), ordered by price.
     */
    public function index(): JsonResponse
    {
        $packages = ListingPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->success(PackageResource::collection($packages));
    }

    /**
     * POST /api/v1/packages/{package}/buy
     *
     * Purchase a listing package to add extra post quota.
     *
     * payment_method=wallet: deducts from wallet and creates a paid payment immediately.
     * payment_method=gateway (default): creates a pending payment against the chosen
     * gateway and returns its hosted checkout URL.
     */
    public function buy(
        BuyPackageRequest $request,
        ListingPackage $package,
        PlanPurchaseService $purchases,
        CheckoutUrlResolver $checkoutUrls,
    ): JsonResponse {
        if (! $package->is_active) {
            return $this->error('This package is no longer available.', 404);
        }

        $user = $request->user();
        $isWallet = $request->input('payment_method', 'gateway') === 'wallet';
        $gateway = $isWallet ? 'wallet' : (string) $request->input('gateway');

        try {
            $payment = $purchases->purchasePackage($user, $package, $gateway, $request->input('coupon_code'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if ($isWallet) {
            return $this->created([
                'payment_id' => $payment->id,
                'package' => new PackageResource($package),
                'post_quota_added' => (int) $package->post_quota,
                'expires_in_days' => (int) $package->duration_days,
                'amount_charged' => (int) $payment->amount,
                'currency' => $payment->currency,
                'status' => 'paid',
            ], 'Package purchased. '.$package->post_quota.' post slot(s) added.');
        }

        $checkoutUrl = $this->resolveCheckoutUrlOrAbandon($payment, $purchases, $checkoutUrls);

        if ($checkoutUrl === null) {
            return $this->error('The payment gateway could not create a checkout session. Please try again.', 422);
        }

        return $this->created([
            'payment_id' => $payment->id,
            'package' => new PackageResource($package),
            'amount' => (int) $payment->amount,
            'currency' => $payment->currency,
            'status' => 'pending',
            'checkout_url' => $checkoutUrl,
        ], 'Payment initiated. Complete the gateway checkout to activate the package.');
    }

    /**
     * Resolve the hosted checkout URL; on failure void the payment so nothing is
     * left permanently stuck as pending.
     */
    private function resolveCheckoutUrlOrAbandon(Payment $payment, PlanPurchaseService $purchases, CheckoutUrlResolver $checkoutUrls): ?string
    {
        try {
            $url = $checkoutUrls->resolve($payment);
        } catch (InvalidArgumentException|RuntimeException) {
            $url = null;
        }

        if ($url === null) {
            $purchases->abandon($payment);
        }

        return $url;
    }
}
