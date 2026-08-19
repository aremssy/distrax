<?php

namespace App\Services\Payment;

use App\Models\HotelBooking;
use App\Models\ListingPackage;
use App\Models\Payment;
use App\Models\TechnicianBooking;
use App\Models\UserSubscription;
use App\Models\Wallet;
use App\Services\WalletService;

class PaymentActivationService
{
    public function __construct(public WalletService $walletService) {}

    /**
     * Activate the payable associated with a successfully paid Payment.
     * Safe to call multiple times — each branch is idempotent.
     */
    public function activate(Payment $payment): void
    {
        $payable = $payment->payable;

        if (! $payable) {
            return;
        }

        match (true) {
            $payable instanceof UserSubscription => $this->activateSubscription($payable),
            $payable instanceof Wallet => $this->topupWallet($payable, $payment),
            $payable instanceof HotelBooking => $payable->update(['status' => 'confirmed']),
            // PostEntitlementService counts paid payments for extra post quota; no state
            // change needed there. A "boost" package additionally features the listing
            // the purchase was made for, once payment clears.
            $payable instanceof ListingPackage => $this->applyBoost($payable, $payment),
            // TechnicianBooking: no automatic status change after payment.
            $payable instanceof TechnicianBooking => null,
            default => null,
        };
    }

    private function activateSubscription(UserSubscription $subscription): void
    {
        if ($subscription->status === 'active') {
            return;
        }

        $subscription->update(['status' => 'active']);
    }

    private function topupWallet(Wallet $wallet, Payment $payment): void
    {
        // Guard: idempotency — if a credit for this payment already exists, skip.
        $alreadyCredited = $wallet->transactions()
            ->where('transactionable_type', $payment->getMorphClass())
            ->where('transactionable_id', $payment->id)
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        $this->walletService->credit(
            $wallet,
            $payment->amount,
            "Wallet top-up via {$payment->gateway}",
            $payment
        );
    }

    private function applyBoost(ListingPackage $package, Payment $payment): void
    {
        if (! ($package->features['bump_up'] ?? false) || ! $payment->property_listing_id) {
            return;
        }

        $payment->listing?->update(['is_featured' => true]);
    }
}
