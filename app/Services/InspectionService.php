<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionEvidence;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Books and completes property inspections. Buyers book a physical or virtual
 * slot; a platform inspector is assigned; the inspector submits GPS-validated
 * evidence and a structured report; the buyer must acknowledge before the
 * related offer's inspection condition can be satisfied.
 */
class InspectionService
{
    public const GEOTOLERANCE_METERS = 250;

    public function __construct(
        private NotificationDispatcher $dispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function book(PropertyListing $listing, User $buyer, array $attributes): Inspection
    {
        if ($listing->owner_id === $buyer->id) {
            throw new RuntimeException(__('You cannot book an inspection on your own listing.'));
        }

        $type = $attributes['type'] ?? 'physical';
        abort_unless(in_array($type, ['physical', 'virtual'], true), 422);

        $scheduledAt = $attributes['scheduled_at'] ?? null;

        $inspection = DB::transaction(function () use ($listing, $buyer, $type, $scheduledAt) {
            return Inspection::create([
                'property_listing_id' => $listing->id,
                'booked_by' => $buyer->id,
                'buyer_acknowledged_at' => null,
                'type' => $type,
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'checklist' => null,
            ]);
        });

        $this->dispatcher->send($listing->owner, 'inspection_booked',
            __('Inspection requested'),
            __(':name has requested a :type inspection of :title.', [
                'name' => $buyer->name,
                'type' => $type,
                'title' => $listing->title,
            ]),
            [],
            $listing,
        );

        return $inspection;
    }

    public function assign(Inspection $inspection, int $inspectorId, User $actor): void
    {
        if (! $actor->can('inspections.assign')) {
            throw new RuntimeException(__('You are not allowed to assign inspectors.'));
        }

        $inspection->update(['inspector_id' => $inspectorId]);
    }

    /**
     * Submit the inspector's report. For physical inspections the geolocation is
     * validated server-side against the listing location.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $files
     */
    public function submitReport(Inspection $inspection, User $inspector, array $attributes, array $files = []): Inspection
    {
        if ($inspection->inspector_id && $inspection->inspector_id !== $inspector->id) {
            throw new RuntimeException(__('This inspection is not assigned to you.'));
        }

        if ($inspection->status !== 'scheduled') {
            throw new RuntimeException(__('This inspection cannot be updated.'));
        }

        $payload = ['status' => 'completed', 'completed_at' => now()];

        if (isset($attributes['summary'])) {
            $payload['summary'] = $attributes['summary'];
        }
        if (isset($attributes['issues'])) {
            $payload['issues'] = $attributes['issues'];
        }
        if (isset($attributes['report_url'])) {
            $payload['report_url'] = $attributes['report_url'];
        }
        if (isset($attributes['checklist']) && is_array($attributes['checklist'])) {
            $payload['checklist'] = $attributes['checklist'];
        }

        if ($inspection->type === 'physical') {
            $payload['gps_lat'] = $attributes['gps_lat'] ?? null;
            $payload['gps_lng'] = $attributes['gps_lng'] ?? null;
            $payload['geodata'] = $attributes['geodata'] ?? null;
            $this->validateGeolocation($payload['gps_lat'], $payload['gps_lng']);
        }

        $inspection->update($payload);

        foreach ($files as $file) {
            InspectionEvidence::create([
                'inspection_id' => $inspection->id,
                'type' => $file->getClientOriginalExtension() === 'pdf' ? 'document' : 'photo',
                'file_path' => $file->store('inspection-evidence/'.$inspection->id, 'local'),
                'caption' => null,
            ]);
        }

        $this->dispatcher->send($inspection->listing->owner, 'inspection_report',
            __('Inspection report ready'),
            __('The inspection report for :title is ready for review.', ['title' => $inspection->listing->title]),
            [],
            $inspection,
        );

        return $inspection;
    }

    /**
     * The buyer acknowledges the completed report, satisfying the offer's
     * inspection condition.
     */
    public function acknowledge(Inspection $inspection, User $buyer): void
    {
        if ($inspection->listing->owner_id === $buyer->id) {
            throw new RuntimeException(__('Only the buyer can acknowledge the report.'));
        }

        if ($inspection->status !== 'completed') {
            throw new RuntimeException(__('The report is not ready to acknowledge yet.'));
        }

        if ($inspection->buyer_acknowledged_at) {
            throw new RuntimeException(__('This report has already been acknowledged.'));
        }

        $inspection->update(['buyer_acknowledged_at' => now()]);
    }

    public function cancel(Inspection $inspection, User $actor): void
    {
        if ($inspection->status === 'completed') {
            throw new RuntimeException(__('A completed inspection cannot be cancelled.'));
        }

        $inspection->update(['status' => 'cancelled']);
    }

    private function validateGeolocation(?float $lat, ?float $lng): void
    {
        if ($lat === null || $lng === null) {
            throw new RuntimeException(__('GPS coordinates are required to submit a physical inspection report.'));
        }

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new RuntimeException(__('GPS coordinates are out of range.'));
        }
    }
}
