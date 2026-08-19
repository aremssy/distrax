<?php

namespace App\Services;

use App\Models\Zone;
use App\Support\HomeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class HomePageService
{
    public function __construct(private HomeRepository $homeRepository) {}

    /**
     * @return array{
     *     heroPropertyTypes: array<int, array{value: string, label: string}>,
     *     heroCities: Collection<int, Zone>,
     *     heroAreas: Collection<int, Zone>,
     *     heroPriceRanges: array<int, array{min: int, max: int, label: string}>,
     *     popularZones: Collection<int, Zone>,
     *     homepageStatistics: array<int, array{value: string, title: string, description: string, icon: string}>
     * }
     */
    public function heroData(): array
    {
        $priceBounds = $this->homeRepository->activeListingPriceBounds();

        return [
            'heroPropertyTypes' => $this->homeRepository->activePropertyTypes()
                ->map(fn (string $type): array => [
                    'value' => $type,
                    'label' => Str::headline($type),
                ])
                ->values()
                ->all(),
            'heroCities' => $this->homeRepository->activeZones('city'),
            'heroAreas' => $this->homeRepository->activeZones('area'),
            'heroPriceRanges' => $this->priceRanges($priceBounds['min'], $priceBounds['max']),
            'popularZones' => $this->homeRepository->popularZones(),
            'homepageStatistics' => $this->statistics(),
        ];
    }

    /** @return array<int, array{min: int, max: int, label: string}> */
    private function priceRanges(?int $minimumPrice, ?int $maximumPrice): array
    {
        if ($minimumPrice === null || $maximumPrice === null) {
            return [];
        }

        $rangeSize = max(1, (int) ceil(($maximumPrice - $minimumPrice + 1) / 4));
        $ranges = [];

        for ($rangeMinimum = $minimumPrice; $rangeMinimum <= $maximumPrice; $rangeMinimum += $rangeSize) {
            $rangeMaximum = min($maximumPrice, $rangeMinimum + $rangeSize - 1);
            $ranges[] = [
                'min' => $rangeMinimum,
                'max' => $rangeMaximum,
                'label' => money($rangeMinimum).' - '.money($rangeMaximum),
            ];
        }

        return $ranges;
    }

    /** @return array<int, array{value: string, title: string, description: string, icon: string}> */
    private function statistics(): array
    {
        $values = $this->homeRepository->statisticValues();

        return [
            [
                'value' => Number::abbreviate($values['listings']).'+',
                'title' => __('Properties Listed'),
                'description' => __('Active properties across popular locations.'),
                'icon' => 'house',
            ],
            [
                'value' => Number::abbreviate($values['clients']).'+',
                'title' => __('Happy Clients'),
                'description' => __('Satisfied buyers and tenants found their perfect space.'),
                'icon' => 'users',
            ],
            [
                'value' => Number::abbreviate($values['agents']).'+',
                'title' => __('Expert Agents'),
                'description' => __('Professional agents ready to help you.'),
                'icon' => 'user-round-check',
            ],
            [
                'value' => Number::format($values['rating'], 1).'/5',
                'title' => __('User Rating'),
                'description' => __('Rated by clients from visible marketplace reviews.'),
                'icon' => 'star',
            ],
        ];
    }
}
