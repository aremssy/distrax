<?php

namespace App\Services;

use App\Models\PropertyListing;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListingCsvExporter
{
    public function __construct(private readonly CsvStreamExporter $exporter) {}

    /**
     * Stream an already filtered and sorted listing query as a CSV download.
     *
     * @param  Builder<PropertyListing>  $query
     */
    public function export(Builder $query): StreamedResponse
    {
        $filename = 'listings-'.now()->format('Y-m-d-His').'.csv';

        $columns = ['ID', 'Title', 'Zone', 'Type', 'Price', 'Status', 'Published By', 'Published On', 'Created At'];

        return $this->exporter->stream($filename, $columns, function (Closure $write) use ($query): void {
            $query->chunk(500, function ($listings) use ($write): void {
                foreach ($listings as $listing) {
                    $write([
                        $listing->id,
                        $listing->title,
                        $listing->zone?->name,
                        $listing->type,
                        $listing->price,
                        $listing->status,
                        $listing->owner?->name,
                        $listing->published_at?->toDateString(),
                        $listing->created_at?->toDateString(),
                    ]);
                }
            });
        });
    }
}
