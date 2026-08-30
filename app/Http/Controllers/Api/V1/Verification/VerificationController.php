<?php

namespace App\Http\Controllers\Api\V1\Verification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PropertyListing;
use App\Models\VerificationScore;
use Illuminate\Http\JsonResponse;

class VerificationController extends ApiController
{
    /**
     * GET /api/v1/verify/{reference}
     *
     * Public verification passport lookup by reference ID (what the QR code encodes).
     * No auth required, no seller PII, no document access — works for anonymous scans.
     */
    public function passport(string $reference): JsonResponse
    {
        $score = VerificationScore::with('listing:id,title,slug')
            ->where('reference_id', $reference)
            ->first();

        if (! $score) {
            return $this->error('Verification reference not found.', 404);
        }

        return $this->success([
            'reference_id' => $score->reference_id,
            'listing' => $score->listing ? [
                'title' => $score->listing->title,
                'slug' => $score->listing->slug,
            ] : null,
            'verification_date' => $score->verification_date,
            'expiry_review_date' => $score->expiry_review_date,
            'score' => $score->score,
            'seller_verification_status' => $score->seller_verification_status,
            'title_status' => $score->title_status,
            'ownership_status' => $score->ownership_status,
            'survey_status' => $score->survey_status,
            'physical_inspection_status' => $score->physical_inspection_status,
            'legal_review_status' => $score->legal_review_status,
            'planning_status' => $score->planning_status,
            'disclosure_count' => $score->disclosure_count,
        ]);
    }

    /**
     * GET /api/v1/listings/{listing}/verification
     *
     * Public verification-status summary for a listing's card/detail page — the same
     * data source the <x-verification-badge> Blade component reads on the web.
     */
    public function status(PropertyListing $listing): JsonResponse
    {
        $case = $listing->verificationCase;

        return $this->success([
            'status' => $case?->status ?? 'in_progress',
            'expiry_review_date' => $case?->expiry_review_date,
            'reference_id' => $case?->scores()->latest('verification_date')->value('reference_id'),
        ]);
    }
}
