<?php

namespace App\Http\Controllers\Api\V1\Subscription;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\PlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class PlanController extends ApiController
{
    /**
     * GET /api/v1/plans
     *
     * List all active subscription plans ordered by sort_order.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get();

        return $this->success(PlanResource::collection($plans));
    }

    /**
     * GET /api/v1/plans/{plan}
     *
     * Show a single active plan.
     */
    public function show(SubscriptionPlan $plan): JsonResponse
    {
        if (! $plan->is_active) {
            return $this->error('Plan not found.', 404);
        }

        return $this->success(new PlanResource($plan));
    }
}
