<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\PropertyListing;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared eligibility + state-transition rules for maintenance requests, used by
 * both the API and website controllers so the tenant → owner → technician loop
 * behaves identically regardless of which client drives it.
 */
class MaintenanceRequestService
{
    /**
     * Listings a user may file a maintenance request against: ones they own, or
     * ones they hold an active (non-cancelled/refunded) hotel booking on.
     *
     * @return Collection<int, PropertyListing>
     */
    public function eligibleListingsFor(User $user): Collection
    {
        return PropertyListing::query()
            ->where(fn ($query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas('hotelBookings', fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'refunded'])))
            ->orderBy('title')
            ->get();
    }

    public function canSubmitFor(User $user, PropertyListing $listing): bool
    {
        if ($listing->owner_id === $user->id) {
            return true;
        }

        return $listing->hotelBookings()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->exists();
    }

    /**
     * Advances the request through the loop:
     *  - owner:      open        → assigned     (picks a technician)
     *  - technician: assigned    → in_progress  (starts the job)
     *  - technician: in_progress → completed    (marks the job done)
     *  - tenant:     completed   → closed        (confirms and closes)
     *
     * @return array{ok: bool, message: string, status_code: int}
     */
    public function transition(MaintenanceRequest $maintenanceRequest, User $user, string $status, ?int $technicianId): array
    {
        $transitions = [
            'assigned' => ['from' => 'open', 'role' => 'owner'],
            'in_progress' => ['from' => 'assigned', 'role' => 'technician'],
            'completed' => ['from' => 'in_progress', 'role' => 'technician'],
            'closed' => ['from' => 'completed', 'role' => 'tenant'],
        ];

        $transition = $transitions[$status] ?? null;

        if (! $transition) {
            return ['ok' => false, 'message' => "Unknown status '{$status}'.", 'status_code' => 422];
        }

        if ($maintenanceRequest->status !== $transition['from']) {
            return ['ok' => false, 'message' => "Cannot move to '{$status}' from '{$maintenanceRequest->status}'.", 'status_code' => 422];
        }

        $isAuthorized = match ($transition['role']) {
            'owner' => $maintenanceRequest->owner_id === $user->id,
            'tenant' => $maintenanceRequest->tenant_id === $user->id,
            'technician' => Technician::where('user_id', $user->id)
                ->where('id', $maintenanceRequest->technician_id)
                ->exists(),
        };

        if (! $isAuthorized) {
            return ['ok' => false, 'message' => 'You are not authorized to make this change.', 'status_code' => 403];
        }

        $update = ['status' => $status];

        if ($status === 'assigned') {
            $update['technician_id'] = $technicianId;
        }

        if ($status === 'completed') {
            $update['resolved_at'] = now();
        }

        $maintenanceRequest->update($update);

        return ['ok' => true, 'message' => 'Maintenance request updated.', 'status_code' => 200];
    }
}
