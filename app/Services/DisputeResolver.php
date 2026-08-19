<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DisputeResolver
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Apply an admin's resolution to a dispute — issuing the approved refund,
     * updating the dispute and writing the audit trail — atomically.
     *
     * @param  array{status: string, assigned_to?: int|null, resolution?: string|null, outcome?: string|null, refund_amount?: int|null}  $data
     */
    public function resolve(Dispute $dispute, array $data): void
    {
        DB::transaction(function () use ($data, $dispute): void {
            if (($data['outcome'] ?? null) === 'refund_approved' && $dispute->payment) {
                // A dispute may only issue a refund once. Without this an admin could
                // re-submit "refund_approved" and create duplicate refunds past the paid amount.
                if ($dispute->refund_id !== null) {
                    throw new RuntimeException('This dispute has already been refunded.');
                }

                $payment = $dispute->payment;
                $alreadyRefunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
                $maxRefundable = $payment->amount - $alreadyRefunded;
                $amount = $data['refund_amount'] ?? $maxRefundable;

                if ($amount > $maxRefundable) {
                    throw new RuntimeException(
                        "Refund amount ({$amount}) exceeds the remaining refundable amount ({$maxRefundable})."
                    );
                }

                $refund = Refund::create([
                    'payment_id' => $dispute->payment_id,
                    'requested_by' => $dispute->raised_by,
                    'amount' => $amount,
                    'reason' => $data['resolution'] ?? 'Dispute refund approved.',
                    'status' => 'processed',
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                ]);

                $dispute->refund_id = $refund->id;
                $payment->update([
                    'status' => ($alreadyRefunded + $amount) >= $payment->amount ? 'refunded' : 'partially_refunded',
                ]);
            }

            $dispute->fill([
                'status' => $data['status'],
                'assigned_to' => $data['assigned_to'] ?? $dispute->assigned_to,
                'resolution' => $data['resolution'] ?? $dispute->resolution,
                'outcome' => $data['outcome'] ?? $dispute->outcome,
                'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
            ])->save();

            $this->audit->record('dispute.'.$data['status'], $dispute, $data);
        });
    }
}
