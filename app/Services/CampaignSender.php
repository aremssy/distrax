<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Tenancy;
use App\Models\User;

class CampaignSender
{
    /**
     * Resolve the campaign's target audience, record it on the campaign and mark it sent.
     *
     * @return int the number of users the campaign was sent to
     */
    public function send(Campaign $campaign): int
    {
        $userIds = $this->audienceFor($campaign);

        $campaign->update([
            'sent_to_user_ids' => $userIds,
            'sent_count' => count($userIds),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return count($userIds);
    }

    /**
     * @return array<int, int>
     */
    private function audienceFor(Campaign $campaign): array
    {
        $segments = $campaign->target_segments ?: ['all_users'];

        // Selected segments are additive: the audience is the *union* of every chosen
        // group, so a campaign aimed at "technicians and agents" reaches both groups
        // rather than only users who happen to belong to both.
        if (in_array('all_users', $segments, true)) {
            return User::query()->pluck('id')->toArray();
        }

        // Guard against a campaign whose segments are all unrecognised (e.g. a segment
        // key removed from code): with no matching branch the nested where would be
        // empty and silently target every user. Target no one instead.
        $known = ['verified_users', 'property_owners', 'tenants', 'technicians', 'agents', 'subscribed_users'];
        if (! array_intersect($segments, $known)) {
            return [];
        }

        $query = User::query()->where(function ($outer) use ($segments): void {
            if (in_array('verified_users', $segments, true)) {
                $outer->orWhere('verification_status', 'verified');
            }

            if (in_array('property_owners', $segments, true)) {
                $outer->orWhereHas('listings');
            }

            if (in_array('tenants', $segments, true)) {
                $outer->orWhereIn('id', Tenancy::query()->whereNotNull('tenant_id')->select('tenant_id'));
            }

            if (in_array('technicians', $segments, true)) {
                $outer->orWhereHas('technicianProfile');
            }

            if (in_array('agents', $segments, true)) {
                $outer->orWhereHas('agentProfile');
            }

            if (in_array('subscribed_users', $segments, true)) {
                $outer->orWhereHas('activeSubscription');
            }
        });

        return $query->pluck('id')->toArray();
    }
}
