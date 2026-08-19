<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nearby points of interest (schools, hospitals, markets, transit, etc.) around a
 * listing, via the Google Places "Nearby Search" API. Keyed off the same
 * `google_maps_api_key` setting already used for the embedded map on the listing
 * detail page — no separate credential to configure.
 */
class NearbyAmenitiesService
{
    /**
     * Google Place types worth surfacing to a renter/buyer, and the label shown per type.
     *
     * @var array<string, string>
     */
    private const TYPES = [
        'school' => 'School',
        'hospital' => 'Hospital',
        'supermarket' => 'Supermarket',
        'restaurant' => 'Restaurant',
        'pharmacy' => 'Pharmacy',
        'bus_station' => 'Bus Station',
        'bank' => 'Bank',
        'park' => 'Park',
    ];

    private const RADIUS_METERS = 1500;

    private const CACHE_TTL_MINUTES = 1440;

    /**
     * @return array<int, array{name: string, type: string, category: string, distance_meters: int, rating: float|null}>
     */
    public function near(float $lat, float $lng): array
    {
        $apiKey = setting('google_maps_api_key');

        if (! $apiKey) {
            return [];
        }

        $cacheKey = sprintf('nearby_amenities:%s:%s', round($lat, 4), round($lng, 4));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($lat, $lng, $apiKey): array {
            $results = [];

            foreach (self::TYPES as $type => $category) {
                $results = [...$results, ...$this->fetch($type, $category, $lat, $lng, $apiKey)];
            }

            usort($results, fn (array $a, array $b) => $a['distance_meters'] <=> $b['distance_meters']);

            return array_slice($results, 0, 20);
        });
    }

    /**
     * @return array<int, array{name: string, type: string, category: string, distance_meters: int, rating: float|null}>
     */
    private function fetch(string $type, string $category, float $lat, float $lng, string $apiKey): array
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'location' => "{$lat},{$lng}",
            'radius' => self::RADIUS_METERS,
            'type' => $type,
            'key' => $apiKey,
        ]);

        if ($response->failed() || $response->json('status') !== 'OK') {
            if ($response->json('status') && ! in_array($response->json('status'), ['OK', 'ZERO_RESULTS'], true)) {
                Log::warning('[Places] Nearby search failed', ['type' => $type, 'status' => $response->json('status')]);
            }

            return [];
        }

        return collect($response->json('results', []))
            ->take(5)
            ->map(fn (array $place) => [
                'name' => $place['name'],
                'type' => $type,
                'category' => $category,
                'distance_meters' => (int) round($this->haversineMeters(
                    $lat, $lng,
                    $place['geometry']['location']['lat'],
                    $place['geometry']['location']['lng']
                )),
                'rating' => $place['rating'] ?? null,
            ])
            ->all();
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
