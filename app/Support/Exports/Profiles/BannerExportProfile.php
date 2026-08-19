<?php

namespace App\Support\Exports\Profiles;

use App\Models\Banner;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BannerExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'banners';
    }

    public function permission(): string
    {
        return 'banners.view';
    }

    public function title(): string
    {
        return 'Banners';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'position' => 'Position',
            'link' => 'Link',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'starts_at' => 'Starts',
            'ends_at' => 'Ends',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors BannerController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Banner::query()
            ->when($request->string('position')->value(), fn ($query, string $position) => $query->where('position', $position))
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->latest();
    }

    /**
     * @param  Banner  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'position' => $row->position,
            'link' => $row->link,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'starts_at' => $row->starts_at?->toDateTimeString(),
            'ends_at' => $row->ends_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
