<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Payment;
use App\Models\User;
use InvalidArgumentException;

class CouponService
{
    /**
     * Validate a coupon code and compute the discount for a given purchase amount.
     *
     * @return array{coupon: Coupon, code: string, type: string, original_amount: int, discount: int, final_amount: int, currency: string}
     *
     * @throws InvalidArgumentException when validation fails
     */
    public function validateAndCompute(string $code, int $amount, User $user, ?string $context = null): array
    {
        $coupon = Coupon::active()->where('code', strtoupper($code))->first();

        if (! $coupon) {
            throw new InvalidArgumentException('Invalid or expired coupon code.');
        }

        if ($coupon->applicable_for && $context && $coupon->applicable_for !== $context) {
            throw new InvalidArgumentException("This coupon is not applicable for {$context}.");
        }

        // Count live paid redemptions rather than a stored `used_count` counter, which
        // nothing maintained — so the global cap was never actually enforced.
        if ($coupon->max_uses !== null) {
            $globalUsageCount = Payment::where('coupon_id', $coupon->id)
                ->where('status', 'paid')
                ->count();

            if ($globalUsageCount >= $coupon->max_uses) {
                throw new InvalidArgumentException('This coupon has reached its usage limit.');
            }
        }

        $userUsageCount = Payment::where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->where('status', 'paid')
            ->count();

        if ($userUsageCount >= $coupon->max_uses_per_user) {
            throw new InvalidArgumentException('You have already used this coupon the maximum number of times.');
        }

        if ($amount < (int) $coupon->min_order) {
            throw new InvalidArgumentException(
                'Minimum order amount for this coupon is '.number_format((int) $coupon->min_order).'.'
            );
        }

        $discount = $this->computeDiscount($coupon, $amount);
        $finalAmount = max(0, $amount - $discount);

        return [
            'coupon' => $coupon,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'original_amount' => $amount,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'currency' => setting('default_currency', 'BDT'),
        ];
    }

    private function computeDiscount(Coupon $coupon, int $amount): int
    {
        if ($coupon->type === 'percentage') {
            $discount = (int) round($amount * $coupon->value / 100);

            return $coupon->max_discount !== null
                ? min($discount, (int) $coupon->max_discount)
                : $discount;
        }

        return min((int) $coupon->value, $amount);
    }
}
