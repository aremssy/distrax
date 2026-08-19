<?php

namespace App\Services;

use App\Models\Zone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZoneCsvImporter
{
    private const TYPES = ['country', 'state', 'city', 'area'];

    /**
     * Import zones from a CSV file, resolving parents by slug and de-duplicating slugs
     * against both the existing zones and the rows already created in this batch.
     *
     * Rows without a name are skipped.
     *
     * The whole file is one transaction rather than one per row: each row is a single
     * insert (nothing to make atomic on its own), but the rows are interdependent —
     * a later row can resolve its parent_id against a zone created by an earlier one.
     * Committing a partial file would leave orphaned zones and a created count that
     * does not match what the admin is told was imported.
     *
     * @return array{created: int, skipped: int}
     */
    public function import(UploadedFile $file): array
    {
        return DB::transaction(fn (): array => $this->importRows($file));
    }

    /**
     * @return array{created: int, skipped: int}
     */
    private function importRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        $rawHeaders = fgetcsv($handle);
        $headers = array_map(fn ($h) => strtolower(trim($h)), $rawHeaders);

        $col = fn (string $name) => ($i = array_search($name, $headers)) !== false ? $i : null;

        $nameCol = $col('name');
        $typeCol = $col('type');
        $parentSlugCol = $col('parent_slug');
        $slugCol = $col('slug');
        $latCol = $col('lat');
        $lngCol = $col('lng');
        $activeCol = $col('is_active');

        $slugMap = Zone::pluck('id', 'slug')->all();
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = $nameCol !== null ? trim($row[$nameCol] ?? '') : '';

            if ($name === '') {
                $skipped++;

                continue;
            }

            $type = $typeCol !== null ? trim($row[$typeCol] ?? 'area') : 'area';
            $parentSlug = $parentSlugCol !== null ? trim($row[$parentSlugCol] ?? '') : '';
            $lat = $latCol !== null && ($row[$latCol] ?? '') !== '' ? (float) $row[$latCol] : null;
            $lng = $lngCol !== null && ($row[$lngCol] ?? '') !== '' ? (float) $row[$lngCol] : null;
            $isActive = $activeCol !== null ? (bool) ($row[$activeCol] ?? 1) : true;

            $slug = $slugCol !== null && ! empty($row[$slugCol])
                ? trim($row[$slugCol])
                : Str::slug($name);

            $slug = $this->uniqueSlug($slug, $slugMap);

            $parentId = $parentSlug !== '' ? ($slugMap[$parentSlug] ?? null) : null;
            $validType = in_array($type, self::TYPES) ? $type : 'area';

            $zone = Zone::create([
                'name' => $name,
                'slug' => $slug,
                'type' => $validType,
                'parent_id' => $parentId,
                'lat' => $lat,
                'lng' => $lng,
                'is_active' => $isActive,
            ]);

            $slugMap[$slug] = $zone->id;
            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Ensure slug uniqueness within the import batch.
     *
     * @param  array<string, int>  $slugMap
     */
    private function uniqueSlug(string $slug, array $slugMap): string
    {
        $base = $slug;
        $i = 1;

        while (isset($slugMap[$slug])) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
