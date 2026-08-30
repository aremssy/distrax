<?php

namespace App\Services;

use App\Models\AskDistraxQuery;
use App\Models\ComparableProperty;
use App\Models\DealScore;
use App\Models\Disclosure;
use App\Models\PropertyListing;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Models\Valuation;
use Illuminate\Support\Str;

/**
 * Retrieval-grounded Q&A scoped to a single property. Answers are generated
 * ONLY from the property's structured / verified data — never invented. Every
 * generated sentence is tagged: verified_fact / estimate / recommendation.
 */
class AskDistraxService
{
    /**
     * Answer a buyer's question about a single property.
     *
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    public function answer(PropertyListing $listing, string $question): array
    {
        $q = Str::lower($question);

        if ($this->matches($q, ['why', 'deal', 'worth it', 'worthwhile', 'investment'])) {
            return $this->answerWhyDeal($listing);
        }

        if ($this->matches($q, ['verified', 'document', 'title', 'checks done', 'verification'])) {
            return $this->answerVerifiedDocuments($listing);
        }

        if ($this->matches($q, ['risk', 'risk ', 'disclosed', 'red flag', 'problem', 'issue', 'hidden'])) {
            return $this->answerDisclosedRisks($listing);
        }

        if ($this->matches($q, ['price', 'compare', 'similar', 'under market', 'overpriced', 'value', 'worth'])) {
            return $this->answerPriceCompare($listing);
        }

        if ($this->matches($q, ['inspection', 'ask the inspector', 'what to ask', 'check during'])) {
            return $this->answerInspection($listing);
        }

        if ($this->matches($q, ['professional', 'further checks', 'survey', 'lawyer', 'engineer', 'due diligence'])) {
            return $this->answerProfessionalChecks($listing);
        }

        return $this->answerNotAvailable();
    }

    /**
     * Persist the query (and optional helpfulness signal) for audit / quality review.
     */
    public function log(PropertyListing $listing, string $question, array $result, ?User $user = null, ?string $sessionId = null): AskDistraxQuery
    {
        return AskDistraxQuery::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'query' => $question,
            'response' => $result['answer'],
            'context' => [
                'property_listing_id' => $listing->id,
                'model' => 'retrieval-grounded-template-v1',
                'answer_type' => $result['answer_type'],
            ],
        ]);
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerWhyDeal(PropertyListing $listing): array
    {
        $score = $listing->dealScores()->latest('computed_at')->first();
        $valuation = $listing->valuations()->latest('valued_at')->first();
        $market = $valuation?->estimated_value;
        $discount = $market && $listing->price ? round((1 - $listing->price / $market) * 100, 1) : null;

        $lines = [];

        if ($market && $listing->price && $discount !== null) {
            if ($discount > 0) {
                $lines[] = "[verified_fact] The asking price is {$discount}% below the most recent estimated market value of {$this->fmt($market)}.";
            } else {
                $lines[] = "[verified_fact] The asking price of {$this->fmt($listing->price)} is not below the most recent estimated market value of {$this->fmt($market)}.";
            }
        } else {
            $lines[] = '[verified_fact] Price compared to market value is not yet available for this property.';
        }

        if ($score && $score->score !== null) {
            $lines[] = "[estimate] The computed Deal Score is {$score->score} out of 100, based on stored underwriting breakdown.";
        } else {
            $lines[] = '[estimate] A Deal Score has not yet been computed for this property.';
        }

        if ($listing->distress_reason_category) {
            $lines[] = "[verified_fact] The seller has indicated a motivation of ".str_replace('_', ' ', $listing->distress_reason_category).'.';
        } else {
            $lines[] = '[verified_fact] No seller motivation is disclosed for this property.';
        }

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'recommendation',
            'sources' => ['valuation', 'deal_score', 'listing'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerVerifiedDocuments(PropertyListing $listing): array
    {
        $verified = $listing->verificationCases()->latest('id')->first();

        if (! $verified) {
            return [
                'answer' => "[verified_fact] No verification case has been recorded for this property, so verified-document status is not available.",
                'answer_type' => 'verified_fact',
                'sources' => ['verification_case'],
            ];
        }

        $lines = ["[verified_fact] The property verification case (#{$verified->id}) currently has the status: ".str_replace('_', ' ', $verified->status ?? 'unknown').'.'];
        $lines[] = '[recommendation] Confirm which documents the seller has actually uploaded before you rely on them.';

        foreach ($verified->scores()->latest('id')->take(3)->get() as $vs) {
            $lines[] = "[verified_fact] Overall verification score for this property: {$vs->score}.";
        }

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'verified_fact',
            'sources' => ['verification_case', 'verification_scores'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerDisclosedRisks(PropertyListing $listing): array
    {
        $disclosures = $listing->disclosures()->get();
        $risks = $listing->riskAssessments()->get();

        if ($disclosures->isEmpty() && $risks->isEmpty()) {
            return [
                'answer' => "[verified_fact] No risks or disclosures are currently recorded for this property. Absence of a record is not a guarantee.",
                'answer_type' => 'verified_fact',
                'sources' => ['disclosures', 'risk_assessments'],
            ];
        }

        $lines = [];
        foreach ($disclosures as $d) {
            $lines[] = "[verified_fact] Disclosed item — ".str_replace('_', ' ', $d->category ?? 'general').($d->description ? ': '.$d->description : '');
        }
        foreach ($risks as $r) {
            $lines[] = "[estimate] Risk area '".str_replace('_', ' ', $r->risk_area ?? 'general')."' assessed as {$r->level}.".($r->why_explanation ? ' '.$r->why_explanation : '');
        }

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'verified_fact',
            'sources' => ['disclosures', 'risk_assessments'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerPriceCompare(PropertyListing $listing): array
    {
        $valuation = $listing->valuations()->latest('valued_at')->first();
        $comparables = $listing->comparableProperties()->latest('id')->take(5)->get();

        if ($valuation) {
            $lines[] = "[estimate] The latest valuation estimate is {$this->fmt($valuation->estimated_value)} (confidence {$valuation->confidence_score}).";
        } else {
            $lines[] = '[verified_fact] No valuation estimate is available for this property.';
        }

        if ($comparables->isEmpty()) {
            $lines[] = '[verified_fact] No comparable sales are recorded for this property.';
        } else {
            $lines[] = '[verified_fact] Comparable sales recorded:';
            foreach ($comparables as $c) {
                $sim = $c->similarity_score !== null ? ' similarity '.$c->similarity_score : '';
                $lines[] = "[verified_fact] — {$this->fmt($c->sale_price)} at ".(string) $c->address.' ('.(string) $c->distance_km.' km, '.$sim.')';
            }
        }

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'estimate',
            'sources' => ['valuation', 'comparable_properties'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerInspection(PropertyListing $listing): array
    {
        $risks = $listing->riskAssessments()->get();
        $lines = ['[recommendation] During inspection, focus on:'];

        if ($risks->isNotEmpty()) {
            foreach ($risks->take(5) as $r) {
                $lines[] = "[recommendation] — Verify '".str_replace('_', ' ', $r->risk_area ?? 'general')."' (assessed {$r->level}) in person.";
            }
        } else {
            $lines[] = '[recommendation] — Confirm the physical condition and title documents in person.';
        }

        $lines[] = '[recommendation] Bring the seller disclosures with you and check each item against the actual property.';

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'recommendation',
            'sources' => ['risk_assessments', 'disclosures'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerProfessionalChecks(PropertyListing $listing): array
    {
        $lines = [
            '[recommendation] Consider ordering an independent valuation or survey.',
            '[recommendation] Have a lawyer review the title and any legal_matter on the deal before closing.',
            '[recommendation] Confirm ownership authority (Power of Attorney, executor, or estate documents) with the seller.',
        ];

        if ($listing->verificationCase?->status !== 'distrax_verified') {
            $lines[] = '[recommendation] Secure a full property verification before committing funds.';
        }

        return [
            'answer' => implode("\n", $lines),
            'answer_type' => 'recommendation',
            'sources' => ['recommendation-engine'],
        ];
    }

    /**
     * @return array{answer: string, answer_type: string, sources: array<int, string>}
     */
    private function answerNotAvailable(): array
    {
        return [
            'answer' => "[verified_fact] I can only answer from this property's verified data. Try asking about why it's a deal, which documents are verified, disclosed risks, how the price compares, or what to check at inspection.",
            'answer_type' => 'verified_fact',
            'sources' => [],
        ];
    }

    private function matches(string $q, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($q, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function fmt(int|float|null $value): string
    {
        if ($value === null) {
            return 'not available';
        }

        return '₦'.number_format((float) $value);
    }

    private function label(string $value): string
    {
        return str_replace('_', ' ', $value);
    }
}
