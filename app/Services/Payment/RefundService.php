<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundService
{
    public function __construct(public GatewayFactory $gatewayFactory) {}

    /**
     * Process a refund for a paid payment.
     *
     * @param  int  $amount  Amount in the same unit as Payment::$amount (0 = full refund).
     *
     * @throws RuntimeException When the payment isn't refundable or the gateway rejects the refund.
     */
    public function refund(Payment $payment, User $requestedBy, int $amount, string $reason): Refund
    {
        // Serialize concurrent refunds for the same payment. Without this, two requests
        // both read "nothing refunded yet", both pass the cap check, and both refund in
        // full — double-spend. A cache lock avoids holding a DB lock across the gateway call.
        $lock = Cache::lock("refund:payment:{$payment->id}", 15);

        if (! $lock->get()) {
            throw new RuntimeException('Another refund for this payment is already in progress.');
        }

        try {
            return $this->processRefund($payment->refresh(), $requestedBy, $amount, $reason);
        } finally {
            $lock->release();
        }
    }

    private function processRefund(Payment $payment, User $requestedBy, int $amount, string $reason): Refund
    {
        if ($payment->status !== 'paid') {
            throw new RuntimeException('Only payments with status "paid" can be refunded.');
        }

        if ($payment->gateway === 'wallet') {
            throw new RuntimeException('Wallet payments cannot be refunded through the gateway. Contact support.');
        }

        // Default to full refund if amount is 0 or not provided.
        if ($amount <= 0) {
            $amount = $payment->amount;
        }

        $alreadyRefunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
        $maxRefundable = $payment->amount - $alreadyRefunded;

        if ($amount > $maxRefundable) {
            throw new RuntimeException(
                "Refund amount ({$amount}) exceeds the remaining refundable amount ({$maxRefundable})."
            );
        }

        $driver = $this->gatewayFactory->make($payment->gateway);
        $gatewayResponse = $driver->refund($payment, $amount, $reason);

        return DB::transaction(function () use ($payment, $requestedBy, $amount, $reason, $gatewayResponse) {
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'requested_by' => $requestedBy->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
                'processed_by' => $requestedBy->id,
                'processed_at' => now(),
            ]);

            $totalRefunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
            $newStatus = $totalRefunded >= $payment->amount ? 'refunded' : 'partially_refunded';

            $payment->update([
                'status' => $newStatus,
                'gateway_response' => $gatewayResponse,
            ]);

            return $refund;
        });
    }
}
