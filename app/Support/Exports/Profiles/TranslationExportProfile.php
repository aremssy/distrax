<?php

namespace App\Support\Exports\Profiles;

use App\Models\Language;
use App\Models\Translation;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TranslationExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'translations';
    }

    public function permission(): string
    {
        return 'settings.view';
    }

    public function title(): string
    {
        return 'Translations';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'language' => 'Language',
            'group' => 'Group',
            'key' => 'Key',
            'value' => 'Value',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 50;
    }

    /**
     * Mirrors TranslationController@index — resolves the active locale the same way and
     * applies the same key/value search, then orders by group and key.
     */
    public function query(Request $request): Builder
    {
        $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
        $localeId = $request->integer('language_id', $languages->firstWhere('is_default', true)?->id ?? $languages->first()?->id);
        $search = $request->string('search');

        return Translation::with('language:id,name,code')
            ->where('language_id', $localeId)
            ->when($search->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            }))
            ->orderBy('group')
            ->orderBy('key');
    }

    /**
     * @param  Translation  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'language' => $row->language?->name,
            'group' => $row->group,
            'key' => $row->key,
            'value' => $row->value,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
