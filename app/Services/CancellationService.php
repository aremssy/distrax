<?php

namespace App\Services;

use App\Models\HotelBooking;

class CancellationService
{
    /**
     * Compute the refund entitlement for a booking cancellation.
     *
     * Cancellation policies (set via admin → Settings → 'cancellation_policy'):
     *
     *   flexible        Full refund up to 24 h before check-in; 0% after.
     *   moderate        Full refund up to 5 days before check-in; 0% after.
     *   strict          50% refund up to 7 days before check-in; 0% after.
     *   non_refundable  No refund ever.
     *
     * @return array{
     *     policy: string,
     *     hours_until_checkin: float,
     *     refund_percentage: int,
     *     refundable_amount: int,
     *     reason: string,
     *     is_refundable: bool
     * }
     */
    public function compute(HotelBooking $booking): array
    {
        $policy = setting('cancellation_policy', 'flexible');
        $hoursUntilCheckIn = (float) now()->diffInHours($booking->check_in->startOfDay(), false);
        $amount = (int) $booking->amount;

        [$percentage, $reason] = $this->policyBreakdown($policy, $hoursUntilCheckIn);

        return [
            'policy' => $policy,
            'hours_until_checkin' => max(0.0, $hoursUntilCheckIn),
            'refund_percentage' => $percentage,
            'refundable_amount' => (int) round($amount * $percentage / 100),
            'reason' => $reason,
            'is_refundable' => $percentage > 0,
        ];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function policyBreakdown(string $policy, float $hoursLeft): array
    {
        return match ($policy) {
            'flexible' => $hoursLeft >= 24
                ? [100, 'Full refund — cancelled more than 24 hours before check-in.']
                : [0, 'No refund — cancelled within 24 hours of check-in.'],

            'moderate' => $hoursLeft >= 120 // 5 days
                ? [100, 'Full refund — cancelled more than 5 days before check-in.']
                : [0, 'No refund — cancelled within 5 days of check-in.'],

            'strict' => $hoursLeft >= 168 // 7 days
                ? [50, '50% refund — cancelled more than 7 days before check-in.']
                : [0, 'No refund — cancelled within 7 days of check-in.'],

            'non_refundable' => [0, 'This booking is non-refundable.'],

            default => [0, 'No refund policy configured.'],
        };
    }
}
