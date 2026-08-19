<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgencyCsvImporter
{
    /**
     * Import agencies from a CSV file, resolving owners by email and zones by slug.
     *
     * Rows without a name, or whose owner_email does not match a user, are skipped.
     *
     * The whole file is one transaction rather than one per row: each row is a single
     * insert, so there is nothing to make atomic row-by-row, but a failure midway
     * would commit the agencies created so far while the admin is shown a created
     * count for an import that did not complete.
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
        $ownerEmailCol = $col('owner_email');
        $zoneSlugCol = $col('zone_slug');
        $phoneCol = $col('phone');
        $emailCol = $col('email');
        $websiteCol = $col('website');
        $addressCol = $col('address');
        $verifiedCol = $col('is_verified');

        $zoneMap = Zone::pluck('id', 'slug')->all();
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = $nameCol !== null ? trim($row[$nameCol] ?? '') : '';
            $ownerEmail = $ownerEmailCol !== null ? trim($row[$ownerEmailCol] ?? '') : '';
            $owner = $ownerEmail !== '' ? User::where('email', $ownerEmail)->first() : null;

            if ($name === '' || ! $owner) {
                $skipped++;

                continue;
            }

            $zoneSlug = $zoneSlugCol !== null ? trim($row[$zoneSlugCol] ?? '') : '';

            Agency::create([
                'owner_id' => $owner->id,
                'zone_id' => $zoneSlug !== '' ? ($zoneMap[$zoneSlug] ?? null) : null,
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'phone' => $phoneCol !== null ? ($row[$phoneCol] ?: null) : null,
                'email' => $emailCol !== null ? ($row[$emailCol] ?: null) : null,
                'website' => $websiteCol !== null ? ($row[$websiteCol] ?: null) : null,
                'address' => $addressCol !== null ? ($row[$addressCol] ?: null) : null,
                'is_verified' => $verifiedCol !== null ? (bool) ($row[$verifiedCol] ?? 0) : false,
                'status' => 'active',
            ]);

            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 1;

        while (Agency::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
