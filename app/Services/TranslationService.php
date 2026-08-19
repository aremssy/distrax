<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * On-demand text translation via the Google Cloud Translation API, keyed off the
 * `google_translate_api_key` setting — same settings-backed credential pattern as
 * the maps key and the FCM/Pusher setup, no separate config file to touch.
 */
class TranslationService
{
    private const CACHE_TTL_MINUTES = 10080; // a week — listing text rarely changes

    public function isConfigured(): bool
    {
        return (bool) setting('google_translate_api_key');
    }

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): ?string
    {
        $apiKey = setting('google_translate_api_key');

        if (! $apiKey || trim($text) === '') {
            return null;
        }

        $cacheKey = 'translate:'.md5($text.'|'.$targetLanguage.'|'.$sourceLanguage);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($text, $targetLanguage, $sourceLanguage, $apiKey): ?string {
            $response = Http::asForm()->post('https://translation.googleapis.com/language/translate/v2', array_filter([
                'q' => $text,
                'target' => $targetLanguage,
                'source' => $sourceLanguage,
                'format' => 'text',
                'key' => $apiKey,
            ]));

            if ($response->failed()) {
                Log::warning('[Translate] Request failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('data.translations.0.translatedText');
        });
    }
}
