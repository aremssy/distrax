<?php

namespace App\Http\Controllers\Concerns;

trait GatesRentManagementFeatures
{
    /** @var list<string> */
    private const RENT_MANAGEMENT_FEATURES = [
        'rent_reminders',
        'receipts',
        'income_tracking',
        'expense_tracking',
        'agreement_templates',
        'multi_unit',
    ];

    /**
     * @param  list<string>  $unlockedFeatures
     */
    private function hasRentManagementAccess(array $unlockedFeatures): bool
    {
        return count(array_intersect(self::RENT_MANAGEMENT_FEATURES, $unlockedFeatures)) > 0;
    }

    /**
     * @param  list<string>  $unlockedFeatures
     */
    private function hasFeature(array $unlockedFeatures, string $feature): bool
    {
        return in_array($feature, $unlockedFeatures, true);
    }
}
