<?php

namespace App\Http\Controllers\Api\V1\Subscription;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\SubscriptionPlan;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LimitsController extends ApiController
{
    /**
     * GET /api/v1/me/limits
     *
     * Return the authenticated user's post-limit status and feature gate map.
     * The feature_gates object maps every known feature to a boolean, so the
     * frontend can lock/unlock UI elements without knowing the full feature list.
     */
    public function show(Request $request, PostEntitlementService $entitlement): JsonResponse
    {
        $user = $request->user();
        $summary = $entitlement->summary($user);

        $allFeatures = SubscriptionPlan::active()
            ->whereNotNull('unlocked_features')
            ->pluck('unlocked_features')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $unlockedFeatures = $summary['unlocked_features'];

        $featureGates = collect($allFeatures)
            ->mapWithKeys(fn (string $f) => [$f => in_array($f, $unlockedFeatures, true)])
            ->toArray();

        return $this->success([
            'post_limit' => $summary['post_limit'],
            'base_post_limit' => $summary['base_post_limit'],
            'package_post_quota' => $summary['package_post_quota'],
            'posts_used' => $summary['used_posts'],
            'posts_remaining' => $summary['remaining_posts'],
            'limit_count_rule' => setting('limit_count_rule', 'active_only'),
            'unlocked_features' => $unlockedFeatures,
            'feature_gates' => $featureGates,
            'subscription' => $summary['active_subscription'] ? [
                'id' => $summary['active_subscription']->id,
                'plan' => $summary['active_subscription']->plan?->name,
                'ends_at' => $summary['active_subscription']->ends_at?->toIso8601String(),
                'status' => $summary['active_subscription']->status,
            ] : null,
        ]);
    }
}
