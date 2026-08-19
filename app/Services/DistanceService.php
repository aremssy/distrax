<?php

namespace App\Services;

class DistanceService
{
    /** Earth radius in kilometres. */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Haversine great-circle distance between two WGS-84 coordinates (in km).
     */
    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(sqrt($a));
    }

    /**
     * Haversine distance in metres (for short distances / walking estimates).
     */
    public function haversineMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->haversineKm($lat1, $lng1, $lat2, $lng2) * 1000;
    }

    /**
     * Estimated driving time in minutes.
     *
     * Uses `avg_drive_speed_kmh` from app settings (default 40 km/h — typical
     * urban speed accounting for traffic, traffic lights, and parking).
     */
    public function estimatedDriveMinutes(float $distanceKm): int
    {
        $speedKmh = (float) setting('avg_drive_speed_kmh', 40);

        if ($speedKmh <= 0) {
            $speedKmh = 40;
        }

        return (int) ceil(($distanceKm / $speedKmh) * 60);
    }

    /**
     * Estimated walking time in minutes.
     *
     * Uses `avg_walk_speed_kmh` from app settings (default 5 km/h).
     */
    public function estimatedWalkMinutes(float $distanceKm): int
    {
        $speedKmh = (float) setting('avg_walk_speed_kmh', 5);

        if ($speedKmh <= 0) {
            $speedKmh = 5;
        }

        return (int) ceil(($distanceKm / $speedKmh) * 60);
    }

    /**
     * Human-readable distance string: "1.4 km" or "320 m".
     */
    public function format(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000).' m';
        }

        return round($distanceKm, 1).' km';
    }

    /**
     * Lat/lng delta for a given radius in km — used to build a bounding-box
     * pre-filter before running a more expensive Haversine query.
     *
     * One degree of latitude ≈ 111 km everywhere.
     * One degree of longitude ≈ 111 km × cos(lat) at the given latitude.
     */
    public function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $deltaLat = $radiusKm / 111.0;
        $deltaLng = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return [
            'lat_min' => $lat - $deltaLat,
            'lat_max' => $lat + $deltaLat,
            'lng_min' => $lng - $deltaLng,
            'lng_max' => $lng + $deltaLng,
        ];
    }
}
