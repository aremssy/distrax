<?php

namespace App\Exports;

use App\Support\Exports\ExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * A single Excel export class driven by an ExportProfile and the user's chosen columns.
 * FromQuery + WithChunkReading keep memory flat for large datasets.
 */
class GenericSheetExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * @param  Builder<covariant Model>  $query
     * @param  list<string>  $columns
     */
    public function __construct(
        private readonly ExportProfile $profile,
        private readonly Builder $query,
        private readonly array $columns,
    ) {}

    /**
     * @return Builder<covariant Model>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        $labels = $this->profile->columns();

        return array_map(fn (string $key): string => $labels[$key] ?? $key, $this->columns);
    }

    /**
     * @return list<string|int|float|null>
     */
    public function map($row): array
    {
        $mapped = $this->profile->map($row);

        return array_map(fn (string $key) => $mapped[$key] ?? null, $this->columns);
    }

    public function chunkSize(): int
    {
        return $this->profile->chunkSize();
    }
}
