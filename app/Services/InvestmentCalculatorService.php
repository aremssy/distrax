<?php

namespace App\Services;

use App\Models\InvestmentCalculator;
use App\Models\PropertyListing;
use App\Models\User;

/**
 * Per-strategy investment calculators. The property page's Investment Potential
 * section defaults to the matching buying_for view (see User::BUYING_FOR), but the
 * buyer can switch. Input overrides are stored per user against a listing in
 * investment_calculators.inputs — never mutated onto the listing itself.
 */
class InvestmentCalculatorService
{
    public const VIEW_TYPES = [
        'buy_hold', 'fix_flip', 'development', 'owner_occupier',
        'land_banking', 'commercial',
    ];

    public function __construct(private ValuationService $valuations) {}

    public function defaultView(User $user): string
    {
        return match ($user->buying_for) {
            'my_home' => 'owner_occupier',
            'investment' => 'buy_hold',
            'fix_flip' => 'fix_flip',
            'development' => 'development',
            'land_banking' => 'land_banking',
            'commercial' => 'commercial',
            default => 'buy_hold',
        };
    }

    /**
     * Compute results for a given view + user-supplied inputs. Does not persist.
     */
    public function calculate(string $view, PropertyListing $listing, array $inputs = []): array
    {
        return match ($view) {
            'buy_hold' => $this->buyHold($listing, $inputs),
            'fix_flip' => $this->fixFlip($listing, $inputs),
            'development' => $this->development($listing, $inputs),
            'owner_occupier' => $this->ownerOccupier($listing, $inputs),
            'land_banking' => $this->landBanking($listing, $inputs),
            'commercial' => $this->commercial($listing, $inputs),
            default => throw new \InvalidArgumentException("Unknown calculator view: {$view}"),
        };
    }

    /**
     * Persist a buyer's input overrides (per user + listing, latest wins) and
     * return the fresh results for those inputs.
     */
    public function save(User $user, string $view, PropertyListing $listing, array $inputs): array
    {
        $results = $this->calculate($view, $listing, $inputs);

        InvestmentCalculator::updateOrCreate(
            ['user_id' => $user->id, 'property_listing_id' => $listing->id],
            ['name' => $view, 'inputs' => $inputs, 'results' => $results]
        );

        return $results;
    }

    private function buyHold(PropertyListing $listing, array $inputs): array
    {
        $purchasePrice = (int) ($inputs['purchase_price'] ?? $listing->price);
        $rentEstimate = (int) ($inputs['rent_estimate'] ?? $this->estimateRent($listing));
        $annualRent = $rentEstimate * 12;
        $expensesPct = (float) ($inputs['expenses_pct'] ?? 20) / 100;
        $annualExpenses = $annualRent * $expensesPct;

        $netIncome = $annualRent - $annualExpenses;
        $grossYield = $purchasePrice > 0 ? round(($annualRent / $purchasePrice) * 100, 2) : null;
        $netYield = $purchasePrice > 0 ? round(($netIncome / $purchasePrice) * 100, 2) : null;
        $monthlyCashFlow = round($netIncome / 12, 2);

        return [
            'view' => 'buy_hold',
            'purchase_price' => $purchasePrice,
            'rent_estimate' => $rentEstimate,
            'gross_yield_pct' => $grossYield,
            'net_yield_pct' => $netYield,
            'annual_expenses' => (int) round($annualExpenses),
            'monthly_cash_flow' => $monthlyCashFlow,
        ];
    }

    private function fixFlip(PropertyListing $listing, array $inputs): array
    {
        $purchasePrice = (int) ($inputs['purchase_price'] ?? $listing->price);
        $renovationCost = (int) ($inputs['renovation_cost'] ?? $this->estimateRenovation($listing));
        $projectedResale = (int) ($inputs['projected_resale'] ?? ($listing->expected_market_value ?? $purchasePrice));
        $allIn = $purchasePrice + $renovationCost;
        $grossMargin = $projectedResale - $allIn;
        $marginPct = $allIn > 0 ? round(($grossMargin / $allIn) * 100, 2) : null;

        return [
            'view' => 'fix_flip',
            'purchase_price' => $purchasePrice,
            'renovation_cost' => $renovationCost,
            'projected_resale' => $projectedResale,
            'gross_margin' => $grossMargin,
            'gross_margin_pct' => $marginPct,
        ];
    }

    private function development(PropertyListing $listing, array $inputs): array
    {
        $landSizeSqm = (int) ($inputs['land_size_sqm'] ?? $this->areaSqm($listing));

        return [
            'view' => 'development',
            'land_size_sqm' => $landSizeSqm,
            'planning_context' => $inputs['planning_context'] ?? 'Confirm permitted use and development controls before proceeding.',
            'feasibility_note' => 'Full development feasibility requires planning approval, density, and build-cost inputs.',
        ];
    }

    private function ownerOccupier(PropertyListing $listing, array $inputs): array
    {
        $price = (int) ($inputs['purchase_price'] ?? $listing->price);
        $downPayment = (int) ($inputs['down_payment'] ?? 0);
        $interestRate = (float) ($inputs['interest_rate'] ?? 14) / 100;
        $loanTermYears = (int) ($inputs['loan_term_years'] ?? 20);
        $financed = $price - $downPayment;

        $monthlyPayment = $this->monthlyPayment($financed, $interestRate, $loanTermYears);

        return [
            'view' => 'owner_occupier',
            'price' => $price,
            'down_payment' => $downPayment,
            'financed_amount' => $financed,
            'monthly_payment' => $monthlyPayment,
            'affordability_note' => $this->affordabilityNote($listing),
        ];
    }

    private function landBanking(PropertyListing $listing, array $inputs): array
    {
        $price = (int) ($inputs['purchase_price'] ?? $listing->price);
        $holdHorizonYears = (int) ($inputs['hold_horizon_years'] ?? 5);
        $appreciationRate = (float) ($inputs['appreciation_rate_pct'] ?? 10) / 100;

        $projectedValue = $price * ((1 + $appreciationRate) ** $holdHorizonYears);

        return [
            'view' => 'land_banking',
            'purchase_price' => $price,
            'hold_horizon_years' => $holdHorizonYears,
            'assumed_appreciation_pct' => round($appreciationRate * 100, 1),
            'projected_value' => (int) round($projectedValue),
            'title_note' => 'Confirm unimpeachable title before land-banking; hold duration raises zoning/market-change risk.',
        ];
    }

    private function commercial(PropertyListing $listing, array $inputs): array
    {
        $price = (int) ($inputs['purchase_price'] ?? $listing->price);
        $monthlyRent = (int) ($inputs['monthly_rent'] ?? $this->estimateRent($listing));
        $annualRent = $monthlyRent * 12;
        $occupancyPct = (float) ($inputs['occupancy_pct'] ?? 85) / 100;
        $effectiveRent = $annualRent * $occupancyPct;
        $capRate = $price > 0 ? round(($effectiveRent / $price) * 100, 2) : null;
        $rentPerSqm = (($sqm = $this->areaSqm($listing)) > 0) ? round($annualRent / $sqm, 2) : null;

        return [
            'view' => 'commercial',
            'purchase_price' => $price,
            'monthly_rent' => $monthlyRent,
            'effective_annual_rent' => (int) round($effectiveRent),
            'cap_rate_pct' => $capRate,
            'gross_rent_per_sqm' => $rentPerSqm,
            'lease_context' => 'Confirm existing leases, covenants, and tenant mix before underwriting.',
        ];
    }

    private function estimateRent(PropertyListing $listing): int
    {
        // Rough yield proxy from asking price; replace with real rent comps when available.
        $market = $this->valuations->latestEstimatedValue($listing) ?? $listing->price;

        return (int) round($market * 0.005); // ~6% annual gross yield → monthly
    }

    private function estimateRenovation(PropertyListing $listing): int
    {
        return (int) round(($listing->price ?? 0) * 0.10); // 10% of asking as starting renovation estimate
    }

    private function areaSqm(PropertyListing $listing): int
    {
        if (! $listing->area_sqft) {
            return 0;
        }

        return (int) round($listing->area_sqft / 10.7639);
    }

    private function monthlyPayment(float $principal, float $annualRate, int $years): float
    {
        if ($principal <= 0) {
            return 0.0;
        }
        if ($annualRate <= 0) {
            return round($principal / ($years * 12), 2);
        }

        $monthlyRate = $annualRate / 12;
        $n = $years * 12;

        return round($principal * ($monthlyRate * (1 + $monthlyRate) ** $n) / ((1 + $monthlyRate) ** $n - 1), 2);
    }

    private function affordabilityNote(PropertyListing $listing): string
    {
        return 'Mortgage affordability is an estimate; confirm with a lender based on current rates and income.';
    }
}
