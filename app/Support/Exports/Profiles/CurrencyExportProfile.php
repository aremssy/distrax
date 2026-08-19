<?php

namespace App\Support\Exports\Profiles;

use App\Models\Currency;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CurrencyExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'currencies';
    }

    public function permission(): string
    {
        return 'settings.edit';
    }

    public function title(): string
    {
        return 'Currencies';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            'symbol' => 'Symbol',
            'exchange_rate' => 'Exchange Rate',
            'is_active' => 'Active',
            'is_default' => 'Default',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    /**
     * Mirrors CurrencyController@index (no request filters).
     */
    public function query(Request $request): Builder
    {
        return Currency::orderBy('sort_order')->orderBy('code');
    }

    /**
     * @param  Currency  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'code' => $row->code,
            'symbol' => $row->symbol,
            'exchange_rate' => $row->exchange_rate,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'is_default' => $row->is_default ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
