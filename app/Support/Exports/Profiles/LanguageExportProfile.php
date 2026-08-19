<?php

namespace App\Support\Exports\Profiles;

use App\Models\Language;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LanguageExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'languages';
    }

    public function permission(): string
    {
        return 'settings.edit';
    }

    public function title(): string
    {
        return 'Languages';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'native_name' => 'Native Name',
            'code' => 'Code',
            'direction' => 'Direction',
            'is_active' => 'Active',
            'is_default' => 'Default',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    /**
     * Mirrors LanguageController@index (no request filters).
     */
    public function query(Request $request): Builder
    {
        return Language::orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Language  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'native_name' => $row->native_name,
            'code' => $row->code,
            'direction' => $row->direction,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'is_default' => $row->is_default ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
