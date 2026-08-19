<?php

namespace App\Http\Controllers\Api\V1\Config;

use App\Enums\PropertyType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\CurrencyResource;
use App\Http\Resources\Api\V1\CustomFieldResource;
use App\Http\Resources\Api\V1\LanguageResource;
use App\Http\Resources\Api\V1\TechnicianCategoryResource;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\Language;
use App\Models\TechnicianCategory;
use App\Models\Translation;
use App\Services\Payment\GatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class BootstrapController extends ApiController
{
    /**
     * Property types understood by the system, derived from the PropertyType enum
     * (single source of truth) so new types appear in the API automatically.
     *
     * @return list<array{key: string, label: string}>
     */
    private static function propertyTypeList(): array
    {
        return array_map(
            fn (string $key, string $label): array => ['key' => $key, 'label' => $label],
            array_keys(PropertyType::options()),
            array_values(PropertyType::options()),
        );
    }

    /**
     * GET /api/v1/config/bootstrap
     *
     * Single call that gives clients everything they need to configure themselves:
     * site info, languages, currencies, active payment gateways (no secrets),
     * feature toggles, limits, map provider public key, property types,
     * and technician categories.
     *
     * Response is cached for 5 minutes.
     */
    public function bootstrap(GatewayFactory $gatewayFactory): JsonResponse
    {
        $data = Cache::remember('api.bootstrap', 300, function () use ($gatewayFactory): array {
            $languages = Language::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $currencies = Currency::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $technicianCategories = TechnicianCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $activeGateways = $gatewayFactory->activeGateways();

            return [
                'site_info' => [
                    'name' => setting('site_name', config('app.name')),
                    'tagline' => setting('site_tagline', ''),
                    'email' => setting('site_email', ''),
                    'phone' => setting('site_phone', ''),
                    'logo' => setting('site_logo') ? asset('storage/'.setting('site_logo')) : null,
                    'favicon' => setting('site_favicon') ? asset('storage/'.setting('site_favicon')) : null,
                    'address' => setting('site_address', ''),
                ],
                'languages' => LanguageResource::collection($languages)->resolve(),
                'currencies' => CurrencyResource::collection($currencies)->resolve(),
                'payment_gateways' => array_map(fn (string $g): array => [
                    'key' => $g,
                    'label' => ucfirst($g),
                ], $activeGateways),
                'feature_toggles' => [
                    'hotel_booking' => (bool) setting('hotel_booking_enabled', true),
                    'technician_marketplace' => (bool) setting('technician_marketplace_enabled', true),
                    'reviews' => (bool) setting('reviews_enabled', true),
                    'chat' => (bool) setting('chat_enabled', true),
                    'saved_search_alerts' => (bool) setting('saved_search_alert_enabled', true),
                    'referral' => (bool) setting('referral_enabled', false),
                ],
                'limits' => [
                    'free_post_limit' => (int) setting('free_post_limit', 3),
                    'hotel_min_stay_nights' => (int) setting('hotel_min_stay_nights', 1),
                    'listing_freshness_days' => (int) setting('listing_freshness_days', 30),
                ],
                'map_provider' => [
                    'provider' => setting('map_provider', 'google'),
                    'public_key' => setting('map_public_key', ''),
                ],
                'property_types' => self::propertyTypeList(),
                'technician_categories' => TechnicianCategoryResource::collection($technicianCategories)->resolve(),
                'default_currency' => baseCurrencyCode(),
                'default_language' => $languages->firstWhere('is_default', true)?->code
                    ?? $languages->first()?->code
                    ?? 'en',
            ];
        });

        return $this->success($data);
    }

    /**
     * GET /api/v1/translations/{locale}
     *
     * Return all translation strings for the given locale, grouped by translation group.
     * Response: { "auth": { "login": "Login" }, "common": { "save": "Save" }, ... }
     */
    public function translations(string $locale): JsonResponse
    {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if (! $language) {
            return $this->error("Locale '{$locale}' is not available.", 404);
        }

        $translations = Cache::remember("api.translations.{$locale}", 600, function () use ($language): array {
            return Translation::where('language_id', $language->id)
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key'))
                ->toArray();
        });

        return $this->success($translations);
    }

    /**
     * GET /api/v1/custom-fields?property_type=rent
     *
     * Return active custom fields for a given property type.
     * Used by listing forms to know which extra fields to render.
     */
    public function customFields(Request $request): JsonResponse
    {
        $request->validate([
            'property_type' => ['required', Rule::in(PropertyType::values())],
        ]);

        $type = $request->string('property_type')->value();

        $fields = Cache::remember("api.custom-fields.{$type}", 300, function () use ($type): array {
            return CustomFieldResource::collection(
                CustomField::active()->forType($type)->orderBy('sort_order')->get()
            )->resolve();
        });

        return $this->success($fields);
    }

    /**
     * GET /api/v1/property-types
     *
     * Return the canonical list of property types the platform supports.
     */
    public function propertyTypes(): JsonResponse
    {
        return $this->success(self::propertyTypeList());
    }

    /**
     * GET /api/v1/amenities
     *
     * Return the list of amenity options clients can attach to a listing
     * via the utility_flags JSON field. Configurable via admin settings
     * (property_amenity_options), with sensible defaults.
     */
    public function amenities(): JsonResponse
    {
        $amenities = Cache::remember('api.amenities', 300, function (): array {
            $fromSettings = setting('property_amenity_options');

            if ($fromSettings && is_array($fromSettings)) {
                return $fromSettings;
            }

            return [
                ['key' => 'wifi', 'label' => 'Wi-Fi'],
                ['key' => 'ac', 'label' => 'Air Conditioning'],
                ['key' => 'parking', 'label' => 'Parking'],
                ['key' => 'generator', 'label' => 'Generator / Backup Power'],
                ['key' => 'lift', 'label' => 'Elevator / Lift'],
                ['key' => 'security', 'label' => 'Security Guard'],
                ['key' => 'cctv', 'label' => 'CCTV'],
                ['key' => 'gas', 'label' => 'Gas Line'],
                ['key' => 'water', 'label' => 'Water Supply'],
                ['key' => 'gym', 'label' => 'Gym'],
                ['key' => 'pool', 'label' => 'Swimming Pool'],
                ['key' => 'balcony', 'label' => 'Balcony'],
                ['key' => 'rooftop', 'label' => 'Rooftop Access'],
                ['key' => 'furnished', 'label' => 'Furnished'],
                ['key' => 'semi_furnished', 'label' => 'Semi-Furnished'],
                ['key' => 'intercom', 'label' => 'Intercom'],
                ['key' => 'fire_exit', 'label' => 'Fire Exit'],
            ];
        });

        return $this->success($amenities);
    }
}
