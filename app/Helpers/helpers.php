<?php

use App\Models\Currency;
use App\Models\User;
use App\Services\CurrencyConverter;
use App\Services\PostEntitlementService;
use App\Support\SettingRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingRepository::class)->get($key, $default);
    }
}

if (! function_exists('home_image')) {
    /**
     * Resolve a homepage image slot to a URL: the admin-uploaded file on the
     * public disk when set, otherwise the bundled default asset.
     */
    function home_image(string $key, string $defaultAsset): string
    {
        $path = setting($key);

        return $path
            ? Storage::disk('public')->url($path)
            : asset($defaultAsset);
    }
}

if (! function_exists('brand_palette')) {
    /**
     * Build a Tailwind-style 50–950 shade scale from a single base hex colour,
     * treating the input as the 600 step. Lighter steps mix toward white and
     * darker steps toward black, so tinted surfaces and hover/active states stay
     * coherent when the brand colour is themed at runtime. Falls back to the
     * default brand purple when given anything but a #RRGGBB value.
     *
     * @return array<int, string>
     */
    function brand_palette(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = '5352ed';
        }

        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        // step => [mix amount, target channel] where 255 = white, 0 = black.
        $steps = [
            50 => [0.92, 255], 100 => [0.84, 255], 200 => [0.68, 255],
            300 => [0.48, 255], 400 => [0.24, 255], 500 => [0.12, 255],
            600 => [0.0, 255],
            700 => [0.12, 0], 800 => [0.24, 0], 900 => [0.36, 0], 950 => [0.52, 0],
        ];

        $mix = fn (int $channel, float $amount, int $target): int => (int) round($channel + ($target - $channel) * $amount);

        $palette = [];

        foreach ($steps as $step => [$amount, $target]) {
            $palette[$step] = sprintf(
                '#%02x%02x%02x',
                $mix($r, $amount, $target),
                $mix($g, $amount, $target),
                $mix($b, $amount, $target),
            );
        }

        return $palette;
    }
}

if (! function_exists('currencyData')) {
    /**
     * Cached attributes of an active currency. Cache holds a plain array so
     * entries survive class renames and are shared safely across consumers.
     *
     * @return array{decimal_places: int, decimal_separator: string, thousands_separator: string, symbol_position: string, symbol: string}|null
     */
    function currencyData(string $code): ?array
    {
        $code = strtoupper($code);
        $key = "currency.{$code}";

        $cached = Cache::get($key);

        if (! is_array($cached)) {
            $cached = Currency::where('code', $code)
                ->where('is_active', true)
                ->first()
                ?->only(['decimal_places', 'decimal_separator', 'thousands_separator', 'symbol_position', 'symbol']);

            if ($cached !== null) {
                Cache::put($key, $cached, now()->addDay());
            }
        }

        return $cached;
    }
}

if (! function_exists('baseCurrencyCode')) {
    function baseCurrencyCode(): string
    {
        return Currency::defaultActive()?->code ?? 'USD';
    }
}

if (! function_exists('money')) {
    function money(int|float $amount, ?string $code = null): string
    {
        $code ??= session('currency_code') ?? baseCurrencyCode();

        $currency = currencyData($code);

        if (! is_array($currency)) {
            return number_format((float) $amount, 2);
        }

        $formatted = number_format(
            (float) $amount,
            $currency['decimal_places'],
            $currency['decimal_separator'],
            $currency['thousands_separator']
        );

        return $currency['symbol_position'] === 'before'
            ? $currency['symbol'].$formatted
            : $formatted.$currency['symbol'];
    }
}

if (! function_exists('moneyFrom')) {
    /**
     * Convert an amount from the currency it is stored in into the viewer's currency
     * (session, else base), then format it. Use for prices held in a listing's own
     * currency that must display in whatever the visitor selected.
     */
    function moneyFrom(int|float $amount, ?string $fromCode, ?string $toCode = null): string
    {
        $toCode ??= session('currency_code') ?? baseCurrencyCode();

        $converted = app(CurrencyConverter::class)->convert($amount, $fromCode, $toCode);

        return money($converted, $toCode);
    }
}

if (! function_exists('canUserPost')) {
    function canUserPost(User $user): bool
    {
        return app(PostEntitlementService::class)->canUserPost($user);
    }
}

if (! function_exists('userUnlockedFeatures')) {
    /**
     * @return list<string>
     */
    function userUnlockedFeatures(User $user): array
    {
        return app(PostEntitlementService::class)->unlockedFeatures($user);
    }
}
