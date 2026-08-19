<?php

namespace App\Services;

use App\Models\Zone;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ZoneCsvExporter
{
    public function __construct(private readonly CsvStreamExporter $exporter) {}

    /**
     * Stream every zone as a CSV download, chunked to keep memory flat.
     */
    public function export(): StreamedResponse
    {
        $filename = 'zones-'.now()->format('Y-m-d').'.csv';

        $columns = ['name', 'slug', 'type', 'parent_slug', 'lat', 'lng', 'is_active'];

        return $this->exporter->stream($filename, $columns, function (Closure $write): void {
            Zone::with('parent')
                ->orderBy('type')
                ->orderBy('name')
                ->chunk(200, function (Collection $zones) use ($write): void {
                    foreach ($zones as $zone) {
                        $write([
                            $zone->name,
                            $zone->slug,
                            $zone->type,
                            $zone->parent?->slug ?? '',
                            $zone->lat ?? '',
                            $zone->lng ?? '',
                            $zone->is_active ? 1 : 0,
                        ]);
                    }
                });
        });
    }
}
