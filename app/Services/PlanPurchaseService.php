<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ListingPackage;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Shared purchase flow for subscription plans and listing packages, used by both
 * the website and API controllers so the coupon → payment → wallet-debit →
 * invoice sequence exists exactly once.
 *
 * Wallet purchases settle immediately (paid payment + invoice); gateway
 * purchases create a pending payment carrying the real gateway name, which the
 * checkout redirect and webhooks later complete.
 */
class PlanPurchaseService
{
    public function __construct(
        private CouponService $coupons,
        private WalletService $wallets,
    ) {}

    /**
     * @throws InvalidArgumentException when the coupon is invalid
     * @throws RuntimeException when the wallet balance is insufficient
     */
    public function subscribe(User $user, SubscriptionPlan $plan, string $gateway, bool $autoRenew = false, ?string $couponCode = null): Payment
    {
        $isWallet = $gateway === 'wallet';
        [$amount, $discount, $couponId] = $this->price($couponCode, (int) $plan->price, $user, 'subscription');

        return DB::transaction(function () use ($user, $plan, $gateway, $autoRenew, $isWallet, $amount, $discount, $couponId): Payment {
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays($plan->duration_days),
                'status' => $isWallet ? 'active' : 'pending',
                'auto_renew' => $autoRenew,
            ]);

            $payment = $this->createPayment($user, $subscription, $gateway, $amount, $discount, $couponId, $isWallet);

            if ($isWallet) {
                $this->settleWithWallet($user, $payment, $amount, 'Subscription: '.$plan->name, $subscription, 'subscription');
            }

            return $payment;
        });
    }

    /**
     * @throws InvalidArgumentException when the coupon is invalid
     * @throws RuntimeException when the wallet balance is insufficient
     */
    public function purchasePackage(User $user, ListingPackage $package, string $gateway, ?string $couponCode = null, ?PropertyListing $boostListing = null): Payment
    {
        $isWallet = $gateway === 'wallet';
        [$amount, $discount, $couponId] = $this->price($couponCode, (int) $package->price, $user, 'package');

        return DB::transaction(function () use ($user, $package, $gateway, $isWallet, $amount, $discount, $couponId, $boostListing): Payment {
            $payment = $this->createPayment($user, $package, $gateway, $amount, $discount, $couponId, $isWallet, $boostListing);

            if ($isWallet) {
                $this->settleWithWallet($user, $payment, $amount, 'Listing package: '.$package->name, $package, 'listing_package');

                if ($boostListing && ($package->features['bump_up'] ?? false)) {
                    $boostListing->update(['is_featured' => true]);
                }
            }

            return $payment;
        });
    }

    /**
     * Void a gateway payment whose hosted checkout could not be created, so it
     * can never linger as permanently pending. Pending subscriptions attached to
     * the payment are cancelled alongside it.
     */
    public function abandon(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->update(['status' => 'failed']);

            if ($payment->payable instanceof UserSubscription && $payment->payable->status === 'pending') {
                $payment->payable->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            }
        });
    }

    /** @return array{0: int, 1: int, 2: ?int} final amount, discount, coupon id */
    private function price(?string $couponCode, int $amount, User $user, string $context): array
    {
        if (! $couponCode) {
            return [$amount, 0, null];
        }

        $result = $this->coupons->validateAndCompute($couponCode, $amount, $user, $context);

        return [$result['final_amount'], $result['discount'], $result['coupon']->id];
    }

    private function createPayment(User $user, Model $payable, string $gateway, int $amount, int $discount, ?int $couponId, bool $isWallet, ?PropertyListing $boostListing = null): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->id,
            'property_listing_id' => $boostListing?->id,
            'gateway' => $gateway,
            'amount' => $amount,
            'currency' => setting('default_currency', 'BDT'),
            'status' => $isWallet ? 'paid' : 'pending',
            'coupon_id' => $couponId,
            'discount_amount' => $discount,
        ]);
    }

    private function settleWithWallet(User $user, Payment $payment, int $amount, string $description, Model $invoiceable, string $invoiceType): void
    {
        $this->wallets->debit($this->wallets->findOrCreateForUser($user), $amount, $description, $payment);
        $payment->forceFill(['paid_at' => now()])->save();

        Invoice::create([
            'invoice_number' => 'INV-'.strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'invoiceable_type' => $invoiceable->getMorphClass(),
            'invoiceable_id' => $invoiceable->id,
            'type' => $invoiceType,
            'amount' => $amount,
            'currency' => setting('default_currency', 'BDT'),
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
        ]);
    }
}
