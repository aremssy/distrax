<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact listing card — used in list/search responses.
 */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_cover', true) ?? $this->images->first()
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'price' => (float) $this->price,
            'currency' => $this->currency_code ?? baseCurrencyCode(),
            // Same price converted into and formatted for the viewer's chosen currency.
            'price_display' => moneyFrom((float) $this->price, $this->currency_code),
            'display_currency' => session('currency_code') ?? baseCurrencyCode(),
            'service_charge' => $this->service_charge !== null ? (float) $this->service_charge : null,
            'advance_months' => $this->advance_months,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area_sqft' => $this->area_sqft !== null ? (float) $this->area_sqft : null,
            'furnished' => $this->furnished,
            'parking' => $this->parking,
            'allowed_for' => $this->allowed_for,
            'address' => $this->address,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_verified' => $this->is_verified,
            'cover_image' => $cover
                ? (str_starts_with($cover->path, 'http')
                    ? $cover->path
                    : asset('storage/'.$cover->path))
                : null,
            'zone' => $this->whenLoaded('zone', fn () => [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
                'type' => $this->zone->type,
            ]),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'avatar' => $this->owner->avatar ? asset('storage/'.$this->owner->avatar) : null,
            ]),
            'reviews_count' => $this->when(isset($this->reviews_count), fn () => (int) $this->reviews_count),
            'favorites_count' => $this->when(isset($this->favorites_count), fn () => (int) $this->favorites_count),
            'distance_km' => $this->when(
                isset($this->distance),
                fn () => round((float) $this->distance, 2)
            ),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
