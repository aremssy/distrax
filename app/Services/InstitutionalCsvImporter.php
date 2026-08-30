<?php

namespace App\Services;

use App\Enums\PropertyType;
use App\Models\BulkUploadBatch;
use App\Models\InstitutionalAccount;
use App\Models\PropertyListing;
use App\Models\Zone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Imports a portfolio CSV for an institutional account. Each row becomes a regular
 * PropertyListing in 'pending' status owned by the institutional account's user —
 * there is deliberately NO shortcut around the normal verification flow: listings
 * enter the verification pipeline when they are later promoted to active.
 */
class InstitutionalCsvImporter
{
    /**
     * @return array{created: int, failed: int, total: int}
     */
    public function import(UploadedFile $file, InstitutionalAccount $account, int $uploadedBy): array
    {
        $result = DB::transaction(function () use ($file, $account, $uploadedBy): array {
            return $this->importRows($file, $account, $uploadedBy);
        });

        return $result['counts'];
    }

    /**
     * @return array{counts: array{created: int, failed: int, total: int}}
     */
    private function importRows(UploadedFile $file, InstitutionalAccount $account, int $uploadedBy): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);

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
        $failed = 0;
        $total = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $title = $titleCol !== null ? trim($row[$titleCol] ?? '') : '';
            $type = $typeCol !== null ? trim($row[$typeCol] ?? '') : '';
            $zoneSlug = $zoneSlugCol !== null ? trim($row[$zoneSlugCol] ?? '') : '';
            $zoneId = $zoneMap[$zoneSlug] ?? null;
            $price = $priceCol !== null ? trim($row[$priceCol] ?? '') : '';

            $reason = null;
            if ($title === '') {
                $reason = 'missing title';
            } elseif (! in_array($type, $validTypes, true)) {
                $reason = "invalid type '{$type}'";
            } elseif ($zoneSlug !== '' && ! $zoneId) {
                $reason = "unknown zone '{$zoneSlug}'";
            } elseif (! is_numeric($price)) {
                $reason = 'price must be numeric';
            }

            if ($reason !== null) {
                $failed++;
                $errors[] = ['row' => $total, 'error' => $reason];

                continue;
            }

            PropertyListing::create([
                'owner_id' => $account->user_id,
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

        BulkUploadBatch::create([
            'institutional_account_id' => $account->id,
            'uploaded_by' => $uploadedBy,
            'original_filename' => $file->getClientOriginalName(),
            'status' => $failed > 0 ? 'partial' : 'complete',
            'total_rows' => $total,
            'processed_rows' => $created,
            'created_count' => $created,
            'failed_count' => $failed,
            'error_report' => $errors,
        ]);

        return ['counts' => ['created' => $created, 'failed' => $failed, 'total' => $total]];
    }
}
