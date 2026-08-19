<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures as BaseGatesRentManagementFeatures;
use Illuminate\Http\JsonResponse;

trait GatesRentManagementFeatures
{
    use BaseGatesRentManagementFeatures;

    /**
     * @param  list<string>  $unlockedFeatures
     */
    private function featureGate(array $unlockedFeatures, string $feature, string $message): ?JsonResponse
    {
        return $this->hasFeature($unlockedFeatures, $feature) ? null : $this->error($message, 403);
    }
}
