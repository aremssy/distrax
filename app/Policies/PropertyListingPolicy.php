<?php

namespace App\Policies;

use App\Models\PropertyListing;
use App\Models\User;

class PropertyListingPolicy
{
    /** Any authenticated non-blocked user can initiate a listing (limit guard enforced separately). */
    public function create(User $user): bool
    {
        return ! $user->is_blocked;
    }

    /** Owner or an active agent of the owning agency. */
    public function update(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    public function delete(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    public function uploadMedia(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    public function deleteMedia(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    public function updateStatus(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    public function confirmFreshness(User $user, PropertyListing $listing): bool
    {
        return $this->isOwnerOrAgent($user, $listing);
    }

    private function isOwnerOrAgent(User $user, PropertyListing $listing): bool
    {
        if ($user->id === $listing->owner_id) {
            return true;
        }

        // Agent of the agency that owns the listing
        if ($listing->agency_id) {
            return $listing->agency?->agents()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists() ?? false;
        }

        return false;
    }
}
