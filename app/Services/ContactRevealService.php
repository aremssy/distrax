<?php

namespace App\Services;

use App\Models\ContactReveal;
use App\Models\PropertyListing;
use App\Models\User;

class ContactRevealService
{
    /**
     * Attempt to reveal the owner's phone for a listing.
     *
     * Returns an array with:
     *   phone       – the owner's phone number (masked if denied)
     *   remaining   – reveals remaining this period (null = unlimited)
     *   already_revealed – true if this listing was already revealed this period
     *
     * Throws \Symfony\Component\HttpKernel\Exception\HttpException (429/403) on violation.
     *
     * @return array{phone: string, remaining: int|null, already_revealed: bool}
     */
    public function reveal(User $requester, PropertyListing $listing): array
    {
        $owner = $listing->owner;

        if (! $owner) {
            abort(404, 'Listing owner not found.');
        }

        if ($owner->phone_visibility === 'none') {
            abort(403, 'The listing owner has disabled contact reveals.');
        }

        $limit = (int) setting('contact_reveal_limit', 10);
        $period = now()->format('Y-m');

        // Already revealed this listing this period → return without counting again
        $existing = ContactReveal::where('user_id', $requester->id)
            ->where('property_listing_id', $listing->id)
            ->where('period', $period)
            ->first();

        if ($existing) {
            $used = ContactReveal::where('user_id', $requester->id)
                ->where('period', $period)
                ->count();

            return [
                'phone' => $owner->phone,
                'remaining' => max(0, $limit - $used),
                'already_revealed' => true,
            ];
        }

        // Count distinct listing reveals this period
        $used = ContactReveal::where('user_id', $requester->id)
            ->where('period', $period)
            ->count();

        if ($used >= $limit) {
            abort(429, 'You have used all '.$limit.' contact reveals for this month.');
        }

        ContactReveal::create([
            'user_id' => $requester->id,
            'property_listing_id' => $listing->id,
            'count' => 1,
            'period' => $period,
        ]);

        return [
            'phone' => $owner->phone,
            'remaining' => max(0, $limit - ($used + 1)),
            'already_revealed' => false,
        ];
    }
}
