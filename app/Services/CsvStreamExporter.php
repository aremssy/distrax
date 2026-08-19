<?php

namespace App\Services;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStreamExporter
{
    /**
     * Stream a CSV download without buffering the whole result set in memory.
     *
     * The $rows callback receives a writer closure; call it once per CSV line.
     *
     * @param  array<int, string>  $columns  Header row, written before any data row.
     * @param  Closure(Closure(array<int, mixed>): void): void  $rows
     */
    public function stream(string $filename, array $columns, Closure $rows): StreamedResponse
    {
        return response()->stream(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $columns);

            $rows(function (array $row) use ($handle): void {
                fputcsv($handle, $row);
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
