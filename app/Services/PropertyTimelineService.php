<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\PropertyListing;
use App\Models\PropertyTimelineEvent;

/**
 * Owns the property change history: writes price_history rows and the privacy-aware
 * property_timeline_events feed. Keeps per-module concerns (verification, deals,
 * inspections) able to log events without coupling them to one another.
 */
class PropertyTimelineService
{
    public function recordPriceChange(PropertyListing $listing, ?int $oldPrice, int $newPrice, ?int $changedBy = null): void
    {
        if ($oldPrice !== null && $oldPrice !== $newPrice) {
            PriceHistory::create([
                'property_listing_id' => $listing->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'changed_by' => $changedBy,
                'currency_code' => $listing->currency_code,
                'changed_at' => now(),
            ]);
        }

        $this->event($listing, 'price_change', 'Price updated', 'public',
            ['old_price' => $oldPrice, 'new_price' => $newPrice]);
    }

    public function recordStatusChange(PropertyListing $listing, string $oldStatus, string $newStatus): void
    {
        $this->event($listing, 'status_change', "Status changed from {$oldStatus} to {$newStatus}", 'public',
            ['from' => $oldStatus, 'to' => $newStatus]);
    }

    public function recordListed(PropertyListing $listing): void
    {
        $this->event($listing, 'listed', 'Listing published', 'public');
    }

    public function event(
        PropertyListing $listing,
        string $type,
        ?string $description = null,
        string $privacyLevel = 'public',
        array $meta = []
    ): PropertyTimelineEvent {
        return PropertyTimelineEvent::create([
            'property_listing_id' => $listing->id,
            'event_type' => $type,
            'description' => $description,
            'privacy_level' => $privacyLevel,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }
}
