<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PropertyListing;
use App\Models\User;
use App\Services\ContactRevealService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactRevealController extends ApiController
{
    /**
     * POST /api/v1/listings/{listing}/reveal-contact
     *
     * Returns the listing owner's phone number, subject to:
     *   - owner's phone_visibility setting (403 if 'none')
     *   - per-user monthly reveal quota (429 when exhausted)
     *   - idempotent: revealing the same listing again in the same period is free
     */
    public function __invoke(
        Request $request,
        PropertyListing $listing,
        ContactRevealService $service,
        NotificationDispatcher $dispatcher,
    ): JsonResponse {
        if ($listing->status !== 'active') {
            return $this->error('Listing not found.', 404);
        }

        if ($listing->owner_id === $request->user()->id) {
            return $this->error('You cannot reveal contact for your own listing.', 422);
        }

        $result = $service->reveal($request->user(), $listing);

        // Notify the listing owner about the lead (only on first reveal in the period)
        if (! $result['already_revealed']) {
            $owner = User::find($listing->owner_id);
            if ($owner) {
                $dispatcher->send(
                    user: $owner,
                    type: 'lead',
                    title: 'New lead on '.$listing->title,
                    body: $request->user()->name.' just revealed your contact info.',
                    data: ['listing_id' => $listing->id],
                    notifiable: $listing,
                );
            }
        }

        return $this->success([
            'phone' => $result['phone'],
            'limit' => (int) setting('contact_reveal_limit', 10),
            'remaining' => $result['remaining'],
            'period' => now()->format('Y-m'),
            'already_revealed' => $result['already_revealed'],
        ], $result['already_revealed'] ? 'Contact already revealed this period.' : 'Contact revealed.');
    }
}
