<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;

class TenancyPolicy
{
    public function create(User $user): bool
    {
        return ! $user->is_blocked;
    }

    public function view(User $user, Tenancy $tenancy): bool
    {
        return $this->isOwnerOrAgent($user, $tenancy);
    }

    public function update(User $user, Tenancy $tenancy): bool
    {
        return $this->isOwnerOrAgent($user, $tenancy);
    }

    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $this->isOwnerOrAgent($user, $tenancy);
    }

    private function isOwnerOrAgent(User $user, Tenancy $tenancy): bool
    {
        if ($user->id === $tenancy->owner_id) {
            return true;
        }

        $listing = $tenancy->listing;

        if ($listing?->agency_id) {
            return $listing->agency?->agents()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists() ?? false;
        }

        return false;
    }
}
