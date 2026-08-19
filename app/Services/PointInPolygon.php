<?php

namespace App\Services;

class PointInPolygon
{
    /**
     * Ray-casting point-in-polygon test.
     *
     * @param  list<array{0: float, 1: float}>  $polygon  Ordered [lat, lng] vertices.
     */
    public function contains(array $polygon, float $lat, float $lng): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$latI, $lngI] = $polygon[$i];
            [$latJ, $lngJ] = $polygon[$j];

            $intersects = ($lngI > $lng) !== ($lngJ > $lng)
                && $lat < ($latJ - $latI) * ($lng - $lngI) / ($lngJ - $lngI) + $latI;

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
