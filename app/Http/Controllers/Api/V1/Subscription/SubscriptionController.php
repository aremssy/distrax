<?php

namespace App\Http\Controllers\Api\V1\Subscription;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Subscription\StoreSubscriptionRequest;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\Payment\CheckoutUrlResolver;
use App\Services\PlanPurchaseService;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class SubscriptionController extends ApiController
{
    /**
     * POST /api/v1/subscriptions
     *
     * Subscribe to a plan.
     *
     * payment_method=wallet: deducts from wallet balance and activates immediately.
     * payment_method=gateway (default): creates a pending subscription + pending
     * payment against the chosen gateway and returns its hosted checkout URL.
     */
    public function store(
        StoreSubscriptionRequest $request,
        PlanPurchaseService $purchases,
        CheckoutUrlResolver $checkoutUrls,
    ): JsonResponse {
        $user = $request->user();
        $plan = SubscriptionPlan::find($request->integer('plan_id'));

        // Guard: already has an active subscription
        if ($user->subscriptions()->active()->exists()) {
            return $this->error(
                'You already have an active subscription. Cancel it before subscribing to a new plan.',
                409
            );
        }

        $isWallet = $request->input('payment_method', 'gateway') === 'wallet';
        $gateway = $isWallet ? 'wallet' : (string) $request->input('gateway');

        try {
            $payment = $purchases->subscribe($user, $plan, $gateway, $request->boolean('auto_renew'), $request->input('coupon_code'));
        } catch (InvalidArgumentException|RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $subscription = $payment->payable->load('plan');

        if ($isWallet) {
            return $this->created(new SubscriptionResource($subscription), 'Subscription activated successfully.');
        }

        $checkoutUrl = $this->resolveCheckoutUrlOrAbandon($payment, $purchases, $checkoutUrls);

        if ($checkoutUrl === null) {
            return $this->error('The payment gateway could not create a checkout session. Please try again.', 422);
        }

        return $this->created([
            'subscription' => new SubscriptionResource($subscription),
            'payment' => [
                'id' => $payment->id,
                'amount' => (int) $payment->amount,
                'currency' => $payment->currency,
                'status' => 'pending',
                'checkout_url' => $checkoutUrl,
            ],
        ], 'Subscription created. Complete the gateway checkout to activate it.');
    }

    /**
     * GET /api/v1/me/subscription
     *
     * Show the authenticated user's current (or most recent) subscription.
     */
    public function show(Request $request, PostEntitlementService $entitlement): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscriptions()
            ->with('plan')
            ->orderByDesc('created_at')
            ->first();

        if (! $subscription) {
            return $this->success(null, 'No subscription found.');
        }

        return $this->success(new SubscriptionResource($subscription));
    }

    /**
     * POST /api/v1/me/subscription/cancel
     *
     * Cancel the active subscription. Access ends at the original ends_at date.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (! $subscription) {
            return $this->error('No active subscription to cancel.', 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        return $this->success(
            new SubscriptionResource($subscription->fresh(['plan'])),
            'Subscription cancelled. Access continues until '.($subscription->ends_at?->toDateString() ?? 'end of period').'.'
        );
    }

    /**
     * Resolve the hosted checkout URL; on failure void the payment (and its
     * pending subscription) so nothing is left permanently stuck as pending.
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
