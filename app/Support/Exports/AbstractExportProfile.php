<?php

namespace App\Support\Exports;

use Illuminate\Support\Str;

/**
 * Sensible defaults shared by every export profile. A concrete profile only has to declare
 * key(), permission(), columns(), query(), and map(); everything else is derived.
 */
abstract class AbstractExportProfile implements ExportProfile
{
    public function title(): string
    {
        return Str::headline($this->key());
    }

    public function filename(): string
    {
        return Str::slug($this->key()).'-'.now()->format('Y-m-d-His');
    }

    /**
     * Default to every declared column, in order.
     *
     * @return list<string>
     */
    public function defaultColumns(): array
    {
        return array_keys($this->columns());
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function perPage(): int
    {
        return 25;
    }
}
