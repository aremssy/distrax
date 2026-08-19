<?php

namespace App\Services;

use App\Models\ListingPackage;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\User;
use App\Models\UserSubscription;

class PostEntitlementService
{
    /**
     * @return array{post_limit: int|null, base_post_limit: int|null, package_post_quota: int, used_posts: int, remaining_posts: int|null, unlocked_features: list<string>, active_subscription: UserSubscription|null}
     */
    public function summary(User $user): array
    {
        $subscription = $this->activeSubscription($user);
        $baseLimit = $this->basePostLimit($subscription);
        $packageQuota = $this->packagePostQuota($user);
        $postLimit = $baseLimit === null ? null : $baseLimit + $packageQuota;
        $usedPosts = $this->usedPosts($user);

        return [
            'post_limit' => $postLimit,
            'base_post_limit' => $baseLimit,
            'package_post_quota' => $packageQuota,
            'used_posts' => $usedPosts,
            'remaining_posts' => $postLimit === null ? null : max(0, $postLimit - $usedPosts),
            'unlocked_features' => $this->unlockedFeatures($user),
            'active_subscription' => $subscription,
        ];
    }

    public function effectivePostLimit(User $user): ?int
    {
        return $this->summary($user)['post_limit'];
    }

    /**
     * The feature keys unlocked by the user's active plan.
     *
     * `unlocked_features` is stored either as a map of feature => enabled, or as a plain
     * list of enabled feature names — both shapes are normalised to a list of keys here.
     *
     * @return list<string>
     */
    public function unlockedFeatures(User $user): array
    {
        $features = $this->activeSubscription($user)?->plan?->unlocked_features ?? [];

        $keys = [];
        foreach ($features as $key => $value) {
            // Map entry (feature => bool) vs list entry (index => feature name).
            if (is_string($key)) {
                if ($value) {
                    $keys[] = $key;
                }
            } elseif (is_string($value) && $value !== '') {
                $keys[] = $value;
            }
        }

        return array_values(array_unique($keys));
    }

    public function remainingPosts(User $user): ?int
    {
        return $this->summary($user)['remaining_posts'];
    }

    public function canUserPost(User $user): bool
    {
        $remaining = $this->remainingPosts($user);

        return $remaining === null || $remaining > 0;
    }

    private function activeSubscription(User $user): ?UserSubscription
    {
        return $user->subscriptions()
            ->with('plan')
            ->active()
            ->latest('ends_at')
            ->first();
    }

    private function basePostLimit(?UserSubscription $subscription): ?int
    {
        if ($subscription?->plan) {
            return $subscription->plan->post_limit === 0 ? null : $subscription->plan->post_limit;
        }

        return max(0, (int) setting('free_post_limit', 0));
    }

    private function usedPosts(User $user): int
    {
        $query = PropertyListing::query()
            ->whereBelongsTo($user, 'owner');

        // Drafts are unfinished and never public, so they never consume the post quota.
        return setting('limit_count_rule', 'active_only') === 'include_archived'
            ? $query->whereNotIn('status', ['rejected', 'draft'])->count()
            : $query->where('status', 'active')->count();
    }

    private function packagePostQuota(User $user): int
    {
        return Payment::query()
            ->whereBelongsTo($user)
            ->where('status', 'paid')
            ->where('payable_type', (new ListingPackage)->getMorphClass())
            ->with('payable')
            ->get()
            ->filter(fn (Payment $payment): bool => $this->packagePaymentIsValid($payment))
            ->sum(fn (Payment $payment): int => (int) $payment->payable->post_quota);
    }

    private function packagePaymentIsValid(Payment $payment): bool
    {
        if (! $payment->payable instanceof ListingPackage || ! $payment->paid_at) {
            return false;
        }

        return $payment->paid_at->copy()->addDays($payment->payable->duration_days)->isFuture();
    }
}
