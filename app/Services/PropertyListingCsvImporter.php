<?php

namespace App\Services;

use App\Enums\PropertyType;
use App\Models\Agency;
use App\Models\PropertyListing;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PropertyListingCsvImporter
{
    /**
     * Bulk-create listings for an agency from a CSV file. Every row lands as
     * `pending`, same as a normal owner submission — an admin still reviews before
     * it goes live. Rows missing a required field, or whose zone_slug does not
     * resolve to a known zone, are skipped rather than failing the whole import.
     *
     * @return array{created: int, skipped: int}
     */
    public function import(UploadedFile $file, Agency $agency, User $owner): array
    {
        return DB::transaction(fn (): array => $this->importRows($file, $agency, $owner));
    }

    /**
     * @return array{created: int, skipped: int}
     */
    private function importRows(UploadedFile $file, Agency $agency, User $owner): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        $rawHeaders = fgetcsv($handle);
        $headers = array_map(fn ($h) => strtolower(trim($h)), $rawHeaders);

        $col = fn (string $name) => ($i = array_search($name, $headers)) !== false ? $i : null;

        $titleCol = $col('title');
        $typeCol = $col('type');
        $zoneSlugCol = $col('zone_slug');
        $priceCol = $col('price');
        $descriptionCol = $col('description');
        $bedroomsCol = $col('bedrooms');
        $bathroomsCol = $col('bathrooms');
        $addressCol = $col('address');

        $zoneMap = Zone::pluck('id', 'slug')->all();
        $validTypes = PropertyType::values();
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $title = $titleCol !== null ? trim($row[$titleCol] ?? '') : '';
            $type = $typeCol !== null ? trim($row[$typeCol] ?? '') : '';
            $zoneSlug = $zoneSlugCol !== null ? trim($row[$zoneSlugCol] ?? '') : '';
            $zoneId = $zoneMap[$zoneSlug] ?? null;
            $price = $priceCol !== null ? trim($row[$priceCol] ?? '') : '';

            if ($title === '' || ! in_array($type, $validTypes, true) || ! $zoneId || ! is_numeric($price)) {
                $skipped++;

                continue;
            }

            PropertyListing::create([
                'owner_id' => $owner->id,
                'agency_id' => $agency->id,
                'zone_id' => $zoneId,
                'type' => $type,
                'title' => $title,
                'description' => $descriptionCol !== null ? ($row[$descriptionCol] ?: null) : null,
                'price' => (int) $price,
                'bedrooms' => $bedroomsCol !== null && is_numeric($row[$bedroomsCol] ?? '') ? (int) $row[$bedroomsCol] : null,
                'bathrooms' => $bathroomsCol !== null && is_numeric($row[$bathroomsCol] ?? '') ? (int) $row[$bathroomsCol] : null,
                'address' => $addressCol !== null ? ($row[$addressCol] ?: null) : null,
                'status' => 'pending',
            ]);

            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped];
    }
}
