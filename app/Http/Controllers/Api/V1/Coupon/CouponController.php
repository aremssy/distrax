<?php

namespace App\Http\Controllers\Api\V1\Coupon;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Coupon\ApplyCouponRequest;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CouponController extends ApiController
{
    /**
     * POST /api/v1/coupons/apply
     *
     * Validate a coupon code and return the discounted amount for a given purchase.
     * This endpoint does NOT consume the coupon — it only previews the discount.
     * The coupon is consumed when the payment is marked as paid (Phase 10).
     */
    public function apply(ApplyCouponRequest $request, CouponService $couponService): JsonResponse
    {
        try {
            $result = $couponService->validateAndCompute(
                $request->input('code'),
                $request->integer('amount'),
                $request->user(),
                $request->input('context')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'coupon_id' => $result['coupon']->id,
            'code' => $result['code'],
            'type' => $result['type'],
            'original_amount' => $result['original_amount'],
            'discount' => $result['discount'],
            'final_amount' => $result['final_amount'],
            'currency' => $result['currency'],
            'expires_at' => $result['coupon']->expires_at?->toIso8601String(),
        ], 'Coupon applied successfully.');
    }
}
