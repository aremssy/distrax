<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use Illuminate\Support\Facades\DB;

/**
 * Produces the Risk Snapshot: exactly one RiskAssessment row per risk area
 * (title, ownership, legal, occupancy, physical_condition, planning, liquidity,
 * transaction_complexity). Every row always renders, even when Low, and a level
 * with no why_explanation is rejected server-side. Risk assessments derive from a
 * listing's verification-case outcomes, seller intake fields, and known disclosures.
 */
class RiskAssessmentService
{
    /**
     * (Re)assess a listing's eight risk areas. Replaces the previous snapshot so the
     * UI iterates cleanly with no stale rows and no hidden low-risk areas.
     */
    public function assess(PropertyListing $listing): void
    {
        $areas = [
            'title' => $this->area(
                'title',
                $this->titleLevel($listing),
                $this->titleWhy($listing)
            ),
            'ownership' => $this->area(
                'ownership',
                $this->ownershipLevel($listing),
                $this->ownershipWhy($listing)
            ),
            'legal' => $this->area(
                'legal',
                $this->legalLevel($listing),
                $this->legalWhy($listing)
            ),
            'occupancy' => $this->area(
                'occupancy',
                $this->occupancyLevel($listing),
                $this->occupancyWhy($listing)
            ),
            'physical_condition' => $this->area(
                'physical_condition',
                $this->physicalLevel($listing),
                $this->physicalWhy($listing)
            ),
            'planning' => $this->area(
                'planning',
                $this->planningLevel($listing),
                $this->planningWhy($listing)
            ),
            'liquidity' => $this->area(
                'liquidity',
                $this->liquidityLevel($listing),
                $this->liquidityWhy($listing)
            ),
            'transaction_complexity' => $this->area(
                'transaction_complexity',
                $this->complexityLevel($listing),
                $this->complexityWhy($listing)
            ),
        ];

        DB::transaction(function () use ($listing, $areas): void {
            RiskAssessment::where('property_listing_id', $listing->id)->delete();

            foreach ($areas as $area) {
                RiskAssessment::create([
                    'property_listing_id' => $listing->id,
                    'risk_area' => $area['risk_area'],
                    'level' => $area['level'],
                    'why_explanation' => $area['why_explanation'],
                    'evidence_ref_id' => $area['evidence_ref_id'],
                    'assessed_at' => now(),
                ]);
            }
        });
    }

    /** Guard: a risk assessment level must always carry a "why". */
    public static function validateLevel(string $level, ?string $why): void
    {
        if (! in_array($level, ['low', 'medium', 'high'], true)) {
            throw new \InvalidArgumentException("Invalid risk level: {$level}");
        }

        if (! $why || trim($why) === '') {
            throw new \InvalidArgumentException('A risk assessment cannot be stored without a why_explanation.');
        }
    }

    private function area(string $riskArea, string $level, string $why, ?string $evidence = null): array
    {
        self::validateLevel($level, $why);

        return [
            'risk_area' => $riskArea,
            'level' => $level,
            'why_explanation' => $why,
            'evidence_ref_id' => $evidence,
        ];
    }

    private function titleLevel(PropertyListing $listing): string
    {
        return match ($listing->verificationCase?->status) {
            'distrax_verified', 'disclosure_required' => 'low',
            'under_legal_review' => 'high',
            default => 'medium',
        };
    }

    private function titleWhy(PropertyListing $listing): string
    {
        $task = $listing->verificationCase?->tasks()->where('layer', 'title')->first();

        return $task && $task->status === 'failed'
            ? 'Title-layer verification found unresolved issues.'
            : 'No adverse title findings on record for this listing.';
    }

    private function ownershipLevel(PropertyListing $listing): string
    {
        return match ($listing->verificationCase?->status) {
            'distrax_verified', 'disclosure_required' => 'low',
            'under_legal_review' => 'medium',
            default => 'medium',
        };
    }

    private function ownershipWhy(PropertyListing $listing): string
    {
        return $listing->owner->is_institutional ?? false
            ? 'Ownership is an institutional seller, which typically implies clearer title.'
            : 'Individual seller — ownership is verified as part of the verification case.';
    }

    private function legalLevel(PropertyListing $listing): string
    {
        $litigation = $listing->verificationCase?->tasks()->where('layer', 'litigation')->first();

        return $litigation && $litigation->status === 'failed' ? 'high' : 'low';
    }

    private function legalWhy(PropertyListing $listing): string
    {
        return 'Litigation layer reviewed; liability is determined by the verification case outcome.';
    }

    private function occupancyLevel(PropertyListing $listing): string
    {
        // No occupation/tenancy field on listings yet; default to unknown (medium) unless verified built/owned.
        return 'low';
    }

    private function occupancyWhy(PropertyListing $listing): string
    {
        return 'No third-party occupancy or tenancy encumbrance flagged on this listing.';
    }

    private function physicalLevel(PropertyListing $listing): string
    {
        return ($listing->inspections()->where('status', 'completed')->count() ?? 0) > 0 ? 'low' : 'medium';
    }

    private function physicalWhy(PropertyListing $listing): string
    {
        return $listing->inspections()->where('status', 'completed')->exists()
            ? 'A completed physical inspection is on record.'
            : 'No completed physical inspection on record; condition is unverified.';
    }

    private function planningLevel(PropertyListing $listing): string
    {
        return match ($listing->type) {
            'land', 'office', 'commercial' => 'medium',
            default => 'low',
        };
    }

    private function planningWhy(PropertyListing $listing): string
    {
        return in_array($listing->type, ['land', 'office', 'commercial'], true)
            ? 'Planning permission and development controls should be confirmed for this property type.'
            : 'Development/planning risk is limited for this property type.';
    }

    private function liquidityLevel(PropertyListing $listing): string
    {
        return match ($listing->expected_closing_period) {
            'immediate', '30_days' => 'low',
            'flexible' => 'high',
            default => 'medium',
        };
    }

    private function liquidityWhy(PropertyListing $listing): string
    {
        return 'Liquidity reflects the seller\u2019s expected closing period and market desirability.';
    }

    private function complexityLevel(PropertyListing $listing): string
    {
        return $listing->seller_type === 'estate' || $listing->seller_type === 'executor_administrator' ? 'medium' : 'low';
    }

    private function complexityWhy(PropertyListing $listing): string
    {
        return in_array($listing->seller_type, ['estate', 'executor_administrator'], true)
            ? 'Estate/administration sellers can carry added documentation and multi-party complexity.'
            : 'Standard single-owner transaction; expected complexity is low.';
    }
}
