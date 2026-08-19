<?php

namespace App\Support;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;

class DatabaseTranslationLoader extends FileLoader
{
    /** Groups Laravel's own internals resolve by hardcoded convention (e.g. trans('validation.required')). */
    private const FRAMEWORK_GROUPS = ['auth', 'passwords', 'pagination', 'validation'];

    public function load($locale, $group, $namespace = null): array
    {
        $fileLines = parent::load($locale, $group, $namespace);

        // Vendor/package namespaces (e.g. "pagination::…") are never DB-backed.
        // The translator passes "*" — not null — for ordinary app keys such as
        // __('auth.failed') and flat __('Some string') JSON keys, so those must
        // fall through to the DB override below.
        if ($namespace !== null && $namespace !== '*') {
            return $fileLines;
        }

        // There is no lang/en/{auth,validation,...}.php on disk — those groups
        // are synthesized from the flat lang/en.json / lang/bn.json dictionary
        // so every translated string, including the ones Laravel's validator
        // and auth guard resolve internally, has exactly one source of truth.
        if ($fileLines === [] && in_array($group, self::FRAMEWORK_GROUPS, true)) {
            $fileLines = $this->translateFrameworkGroup($group, $locale);
        }

        $langId = Cache::remember(
            "lang.id.{$locale}",
            now()->addDay(),
            fn () => Language::where('code', $locale)->value('id')
        );

        if (! $langId) {
            return $fileLines;
        }

        $dbLines = Cache::remember(
            "translations.{$locale}.{$group}",
            now()->addDay(),
            fn () => Translation::where('language_id', $langId)
                ->where('group', $group)
                ->pluck('value', 'key')
                ->all()
        );

        return array_merge($fileLines, $dbLines);
    }

    public static function flushForLocale(string $locale): void
    {
        Cache::forget("lang.id.{$locale}");
        // Group-level caches are flushed on next miss (no tag support needed)
    }

    /**
     * Build a validation/auth/passwords/pagination message array by resolving
     * every English default line (from {@see FrameworkLanguageLines}) against
     * the flat JSON dictionary for this locale — the same dictionary that
     * backs every other __('Some string') call in the app.
     *
     * @return array<string, mixed>
     */
    private function translateFrameworkGroup(string $group, string $locale): array
    {
        $defaults = FrameworkLanguageLines::all()[$group] ?? [];

        // The '*' group is the JSON pseudo-group; a distinct group argument
        // here means this can't recurse back into this same branch.
        $flatDictionary = $this->load($locale, '*', '*');

        return $this->translateNested($defaults, $flatDictionary);
    }

    /**
     * @param  array<string, mixed>  $lines
     * @param  array<string, string>  $flatDictionary
     * @return array<string, mixed>
     */
    private function translateNested(array $lines, array $flatDictionary): array
    {
        $translated = [];

        foreach ($lines as $key => $value) {
            $translated[$key] = is_array($value)
                ? $this->translateNested($value, $flatDictionary)
                : ($flatDictionary[$value] ?? $value);
        }

        return $translated;
    }
}
