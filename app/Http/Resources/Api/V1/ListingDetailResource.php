<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full listing detail — used in show responses.
 */
class ListingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'language_tag' => $this->language_tag,
            'translate_available' => (bool) setting('google_translate_api_key')
                && $this->language_tag
                && substr((string) $this->language_tag, 0, 2) !== substr(app()->getLocale(), 0, 2),

            // Pricing
            'price' => (float) $this->price,
            'service_charge' => $this->service_charge !== null ? (float) $this->service_charge : null,
            'advance_months' => $this->advance_months,

            // Physical attributes
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'area_sqft' => $this->area_sqft !== null ? (float) $this->area_sqft : null,
            'parking' => $this->parking,
            'furnished' => $this->furnished,
            'allowed_for' => $this->allowed_for,
            'utility_flags' => $this->utility_flags ?? [],

            // Location
            'address' => $this->address,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'zone' => $this->whenLoaded('zone', fn () => new ZoneResource($this->zone)),

            // Status & flags
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_verified' => $this->is_verified,
            'is_stale' => $this->isStale(),

            // Media
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset('storage/'.$img->path),
                'is_cover' => $img->is_cover,
                'sort_order' => $img->sort_order,
            ])),
            'videos' => $this->whenLoaded('videos', fn () => $this->videos->map(fn ($vid) => [
                'id' => $vid->id,
                'url' => $vid->url,
                'source' => $vid->source,
                'thumbnail' => $vid->thumbnail,
                'sort_order' => $vid->sort_order,
            ])),

            // Custom fields
            'custom_fields' => $this->whenLoaded('customFieldValues', fn () => $this->customFieldValues->mapWithKeys(fn ($v) => [
                $v->field->key ?? $v->custom_field_id => $v->value,
            ])),

            // Owner — the phone number is intentionally omitted here; it is gated behind
            // the paid contact-reveal endpoint (phone_visibility + reveal quota + lead
            // notification). Exposing it on the public listing would bypass that.
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'avatar' => $this->owner->avatar ? asset('storage/'.$this->owner->avatar) : null,
                'is_verified' => $this->owner->verification_status === 'verified',
            ]),

            // Agency — phone omitted for the same reason as the owner above.
            'agency' => $this->whenLoaded('agency', fn () => $this->agency ? [
                'id' => $this->agency->id,
                'name' => $this->agency->name,
                'logo' => $this->agency->logo ? asset('storage/'.$this->agency->logo) : null,
                'is_verified' => $this->agency->is_verified,
            ] : null),

            // Reviews (first page)
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews
                ->where('is_visible', true)
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'rating' => (int) $r->rating,
                    'body' => $r->body,
                    'owner_reply' => $r->owner_reply,
                    'reviewer' => [
                        'id' => $r->reviewer?->id,
                        'name' => $r->reviewer?->name,
                        'avatar' => $r->reviewer?->avatar ? asset('storage/'.$r->reviewer->avatar) : null,
                    ],
                    'created_at' => $r->created_at->toIso8601String(),
                ])
                ->values()),

            'reviews_count' => $this->when(isset($this->reviews_count), fn () => (int) $this->reviews_count),
            'favorites_count' => $this->when(isset($this->favorites_count), fn () => (int) $this->favorites_count),

            // Timestamps
            'needs_confirmation_at' => $this->needs_confirmation_at?->toIso8601String(),
            'last_freshness_check_at' => $this->last_freshness_check_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
