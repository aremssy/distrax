<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\Language;
use App\Models\Translation;
use App\Support\HomeContent;
use App\Support\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private SettingRepository $settings) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'general');

        return view('admin.settings.index', [
            'tab' => $tab,
            'settings' => $this->settings->all(),
            'languages' => $tab === 'localization'
                ? Language::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name', 'is_default'])
                : collect(),
            'currencies' => $tab === 'localization'
                ? Currency::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name', 'is_default'])
                : collect(),
            'emailTemplates' => $tab === 'templates'
                ? EmailTemplate::orderBy('channel')->orderBy('name')->get(['id', 'name', 'channel', 'subject', 'is_active'])
                : collect(),
            // Only whether each secret is set — never the value itself.
            'secretConfigured' => in_array($tab, ['payments', 'mail', 'broadcasting'], true)
                ? collect(SettingRepository::secretKeys())
                    ->mapWithKeys(fn (string $key) => [$key => filled($this->settings->get($key))])
                    ->all()
                : [],
            'activeGateways' => $tab === 'payments'
                ? array_filter(array_map('trim', explode(',', (string) $this->settings->get('active_payment_gateways', ''))))
                : [],
            ...$tab === 'homepage' ? $this->homeContentData($request) : [],
        ]);
    }

    /**
     * Data needed to render the translatable "Homepage" content editor.
     *
     * @return array{homeGroups: array<string, mixed>, homeLanguages: Collection<int, Language>, homeLocaleId: int|null, homeValues: array<string, string>}
     */
    private function homeContentData(Request $request): array
    {
        $languages = Language::where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name', 'is_default']);
        $localeId = $request->integer('lang') ?: ($languages->firstWhere('is_default', true)?->id ?? $languages->first()?->id);

        $fields = HomeContent::fieldsFlat();

        $overridesByText = Translation::where('language_id', $localeId)
            ->where('group', '*')
            ->whereIn('key', $fields)
            ->pluck('value', 'key')
            ->all();

        $values = [];
        foreach ($fields as $slug => $text) {
            if (array_key_exists($text, $overridesByText)) {
                $values[$slug] = $overridesByText[$text];
            }
        }

        return [
            'homeGroups' => HomeContent::groups(),
            'homeLanguages' => $languages,
            'homeLocaleId' => $localeId,
            'homeValues' => $values,
        ];
    }

    /**
     * Persist translatable homepage content for one language.
     *
     * Content is stored the same way as every other `__()` string in the app —
     * group "*", keyed by the English sentence itself — so a blank field (or one
     * matching the English default) deletes its override and falls back to the
     * text baked into the view/controller.
     */
    public function updateHomepage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language_id' => ['required', Rule::exists('languages', 'id')->where('is_active', true)],
            'content' => ['array'],
            'content.*' => ['nullable', 'string', 'max:2000'],
            'images' => ['array'],
            'images.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'images_remove' => ['array'],
        ]);

        $languageId = (int) $validated['language_id'];
        $content = $validated['content'] ?? [];

        $this->saveHomepageImages($request);

        // Only the submitted section's fields are touched, so a section-level
        // save never wipes the other sections.
        $fields = HomeContent::fieldsFlat();

        DB::transaction(function () use ($languageId, $content, $fields): void {
            foreach ($content as $slug => $raw) {
                if (! isset($fields[$slug])) {
                    continue;
                }

                $text = $fields[$slug];
                $value = trim((string) $raw);

                if ($value === '' || $value === $text) {
                    Translation::where('language_id', $languageId)
                        ->where('group', '*')
                        ->where('key', $text)
                        ->delete();

                    continue;
                }

                Translation::updateOrCreate(
                    ['language_id' => $languageId, 'group' => '*', 'key' => $text],
                    ['value' => $value],
                );
            }
        });

        if ($locale = Language::find($languageId)?->code) {
            Cache::forget("translations.{$locale}.*");
            Cache::forget("api.translations.{$locale}");
        }

        return redirect()->route('admin.settings.index', ['tab' => 'homepage', 'lang' => $languageId])
            ->with('success', 'Homepage content saved.');
    }

    /**
     * Store or clear the global homepage image slots. Images are shared across
     * languages, so they live in settings rather than the translations table.
     */
    private function saveHomepageImages(Request $request): void
    {
        $remove = (array) $request->input('images_remove', []);

        foreach (HomeContent::imageKeys() as $key) {
            $current = $this->settings->get($key);

            if (! empty($remove[$key])) {
                if ($current) {
                    Storage::disk('public')->delete($current);
                }

                $this->settings->set($key, '');

                continue;
            }

            if ($request->hasFile("images.{$key}")) {
                $newPath = $request->file("images.{$key}")->store('homepage', 'public');

                if ($current) {
                    Storage::disk('public')->delete($current);
                }

                $this->settings->set($key, $newPath);
            }
        }
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $tab = $request->query('tab', 'general');

        $validated = $request->validated();

        if ($tab === 'limits') {
            $validated['saved_search_alert_enabled'] = $request->boolean('saved_search_alert_enabled') ? '1' : '0';
        }

        if ($tab === 'branding') {
            // A file field never round-trips as a string; replace it with the stored
            // path when a new image is uploaded, otherwise leave the existing one intact.
            foreach (['site_logo' => 'logos', 'site_favicon' => 'branding', 'og_image' => 'branding'] as $field => $folder) {
                if ($request->hasFile($field)) {
                    $newPath = $request->file($field)->store($folder, 'public');

                    if ($oldPath = $this->settings->get($field)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    $validated[$field] = $newPath;
                } else {
                    unset($validated[$field]);
                }
            }
        }

        if ($tab === 'payments') {
            $validated['active_payment_gateways'] = implode(',', $request->input('active_payment_gateways', []));
            $validated['paypal_sandbox'] = $request->boolean('paypal_sandbox') ? '1' : '0';
            $validated['bkash_sandbox'] = $request->boolean('bkash_sandbox') ? '1' : '0';
        }

        // A blank secret field means "keep the current value" — don't overwrite it with
        // an empty string (the form never renders an existing secret back to the browser).
        // Applies to any tab carrying a secret key, e.g. payments and mail.
        foreach (SettingRepository::secretKeys() as $secret) {
            if (array_key_exists($secret, $validated) && blank($validated[$secret])) {
                unset($validated[$secret]);
            }
        }

        // setMany() writes one row per key; a partial save would leave the tab's
        // settings half-old/half-new (e.g. a gateway key updated but its secret not).
        DB::transaction(fn () => $this->settings->setMany($validated));

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'Settings saved.');
    }
}
