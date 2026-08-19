<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleAndCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve locale from Accept-Language header or authenticated user preference
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        // Resolve active currency and share via request macro / container
        $currency = $this->resolveCurrency($request);
        app()->instance('api.currency', $currency);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // Prefer authenticated user's saved language
        if ($request->user()?->language) {
            return $request->user()->language;
        }

        // Fall back to Accept-Language header (first tag only)
        $header = $request->header('Accept-Language', '');
        $tag = strtolower(trim(explode(',', $header)[0]));
        $locale = substr($tag, 0, 2);

        $supported = config('app.supported_locales', ['en']);

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }

    private function resolveCurrency(Request $request): ?object
    {
        // Prefer authenticated user's saved currency, then X-Currency header, then default
        $code = $request->user()?->currency
            ?? $request->header('X-Currency')
            ?? setting('default_currency', 'USD');

        $currency = currencyData($code);

        return $currency !== null ? (object) $currency : null;
    }
}
