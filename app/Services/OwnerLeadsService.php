<?php

namespace App\Services;

use App\Models\ContactReveal;
use App\Models\Conversation;
use App\Models\PropertyListing;
use App\Models\User;
use App\Models\VisitSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OwnerLeadsService
{
    /**
     * A unified, reverse-chronological feed of visit requests, contact reveals, and
     * inbound conversations across the owner's listings (optionally one listing).
     *
     * @return Collection<int, array{type: string, listing_id: int, listing_title: string|null, user_id: int|null, user_name: string|null, note: string|null, occurred_at: Carbon|null}>
     */
    public function feed(User $owner, ?int $listingId = null): Collection
    {
        $listingIds = PropertyListing::whereBelongsTo($owner, 'owner')
            ->when($listingId, fn ($query) => $query->where('id', $listingId))
            ->pluck('id');

        $visits = VisitSchedule::whereIn('property_listing_id', $listingIds)
            ->with(['listing:id,title', 'user:id,name'])
            ->get()
            ->map(fn (VisitSchedule $visit) => [
                'type' => 'visit',
                'listing_id' => $visit->property_listing_id,
                'listing_title' => $visit->listing?->title,
                'user_id' => $visit->user_id,
                'user_name' => $visit->user?->name,
                'note' => $visit->note,
                'occurred_at' => $visit->created_at,
            ]);

        $reveals = ContactReveal::whereIn('property_listing_id', $listingIds)
            ->with(['listing:id,title', 'user:id,name'])
            ->get()
            ->map(fn (ContactReveal $reveal) => [
                'type' => 'contact_reveal',
                'listing_id' => $reveal->property_listing_id,
                'listing_title' => $reveal->listing?->title,
                'user_id' => $reveal->user_id,
                'user_name' => $reveal->user?->name,
                'note' => null,
                'occurred_at' => $reveal->created_at,
            ]);

        $conversations = Conversation::whereIn('property_listing_id', $listingIds)
            ->where('starter_id', '!=', $owner->id)
            ->with(['listing:id,title', 'starter:id,name'])
            ->get()
            ->map(fn (Conversation $conversation) => [
                'type' => 'message',
                'listing_id' => $conversation->property_listing_id,
                'listing_title' => $conversation->listing?->title,
                'user_id' => $conversation->starter_id,
                'user_name' => $conversation->starter?->name,
                'note' => null,
                'occurred_at' => $conversation->created_at,
            ]);

        return $visits->concat($reveals)->concat($conversations)
            ->sortByDesc('occurred_at')
            ->values();
    }

    /**
     * Aggregate view/lead counts, conversion rate, and a per-listing breakdown.
     *
     * @return array{total_views: int, total_leads: int, leads_by_type: array{visits: int, contact_reveals: int, messages: int}, conversion_rate: float, per_listing: Collection<int, array<string, mixed>>}
     */
    public function summary(User $owner, ?int $listingId = null): array
    {
        $listings = PropertyListing::whereBelongsTo($owner, 'owner')
            ->when($listingId, fn ($query) => $query->where('id', $listingId))
            ->withCount(['visitSchedules', 'contactReveals', 'conversations'])
            ->get();

        $totalViews = (int) $listings->sum('view_count');
        $visits = (int) $listings->sum('visit_schedules_count');
        $reveals = (int) $listings->sum('contact_reveals_count');
        $messages = (int) $listings->sum('conversations_count');
        $totalLeads = $visits + $reveals + $messages;

        return [
            'total_views' => $totalViews,
            'total_leads' => $totalLeads,
            'leads_by_type' => [
                'visits' => $visits,
                'contact_reveals' => $reveals,
                'messages' => $messages,
            ],
            'conversion_rate' => $totalViews > 0 ? round(($totalLeads / $totalViews) * 100, 1) : 0.0,
            'per_listing' => $listings->map(fn (PropertyListing $listing) => [
                'id' => $listing->id,
                'title' => $listing->title,
                'views' => (int) $listing->view_count,
                'visits' => (int) $listing->visit_schedules_count,
                'contact_reveals' => (int) $listing->contact_reveals_count,
                'messages' => (int) $listing->conversations_count,
            ])->values(),
        ];
    }
}
