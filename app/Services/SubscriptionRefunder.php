<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;

class SubscriptionRefunder
{
    /**
     * Record a processed refund against the subscription's latest paid payment,
     * update the payment status and cancel the subscription — atomically.
     *
     * Aborts with 422 when the subscription has no refundable payment or when the
     * requested amount exceeds the remaining refundable balance.
     *
     * @param  array{amount: int, reason: string, admin_note?: string|null}  $data
     */
    public function refund(UserSubscription $subscription, array $data): void
    {
        DB::transaction(function () use ($data, $subscription): void {
            $payment = $subscription->payments()
                ->whereIn('status', ['paid', 'partially_refunded'])
                ->latest('paid_at')
                ->lockForUpdate()
                ->first();

            if (! $payment instanceof Payment) {
                abort(422, 'No paid payment exists for this subscription.');
            }

            // Cap against the cumulative total already refunded — otherwise repeated
            // partial refunds could exceed the amount the customer actually paid.
            $alreadyRefunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
            $maxRefundable = $payment->amount - $alreadyRefunded;

            if ($data['amount'] > $maxRefundable) {
                abort(422, "Refund amount ({$data['amount']}) exceeds the remaining refundable amount ({$maxRefundable}).");
            }

            Refund::create([
                'payment_id' => $payment->id,
                'requested_by' => auth()->id(),
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'status' => 'processed',
                'admin_note' => $data['admin_note'] ?? null,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $totalRefunded = $alreadyRefunded + $data['amount'];
            $payment->update(['status' => $totalRefunded >= $payment->amount ? 'refunded' : 'partially_refunded']);

            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
                'refunded_at' => now(),
                'refunded_amount' => $subscription->refunded_amount + $data['amount'],
            ]);
        });
    }
}
