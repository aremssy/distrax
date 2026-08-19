<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\Zone;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchemaMarkupService
{
    public function forListing(PropertyListing $listing): array
    {
        $cover = $listing->images->firstWhere('is_cover', true) ?? $listing->images->first();
        $image = match (true) {
            ! $cover => null,
            $cover->disk === 'remote' || Str::startsWith($cover->path, ['http://', 'https://']) => $cover->path,
            default => Storage::disk($cover->disk)->url($cover->path),
        };

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $listing->title,
            'description' => Str::limit(strip_tags($listing->description ?? ''), 300),
            'image' => $image,
            'url' => route('properties.show', $listing),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $listing->price,
                'priceCurrency' => setting('default_currency', 'BDT'),
                'availability' => $listing->status === 'active' ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            ],
        ];
    }

    public function forArea(Zone $zone): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Place',
            'name' => $zone->name,
            'url' => url("/rent/{$zone->slug}"),
        ];
    }

    public function forOrganization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => setting('site_name', config('app.name')),
            'url' => url('/'),
            'logo' => setting('logo') ? url(setting('logo')) : null,
        ];
    }
}
