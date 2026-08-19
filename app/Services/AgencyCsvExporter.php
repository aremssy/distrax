<?php

namespace App\Services;

use App\Models\Agency;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgencyCsvExporter
{
    public function __construct(private readonly CsvStreamExporter $exporter) {}

    /**
     * Stream every agency as a CSV download, chunked to keep memory flat.
     */
    public function export(): StreamedResponse
    {
        $filename = 'agencies-'.now()->format('Y-m-d').'.csv';

        $columns = ['name', 'slug', 'owner_email', 'zone_slug', 'phone', 'email', 'website', 'address', 'is_verified', 'status'];

        return $this->exporter->stream($filename, $columns, function (Closure $write): void {
            Agency::with(['owner:id,email', 'zone:id,slug'])
                ->orderBy('name')
                ->chunk(200, function (Collection $agencies) use ($write): void {
                    foreach ($agencies as $agency) {
                        $write([
                            $agency->name,
                            $agency->slug,
                            $agency->owner?->email ?? '',
                            $agency->zone?->slug ?? '',
                            $agency->phone ?? '',
                            $agency->email ?? '',
                            $agency->website ?? '',
                            $agency->address ?? '',
                            $agency->is_verified ? 1 : 0,
                            $agency->status,
                        ]);
                    }
                });
        });
    }
}
