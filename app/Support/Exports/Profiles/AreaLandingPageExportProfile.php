<?php

namespace App\Support\Exports\Profiles;

use App\Models\AreaLandingPage;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AreaLandingPageExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'area-landing-pages';
    }

    public function permission(): string
    {
        return 'seo.view';
    }

    public function title(): string
    {
        return 'Area Landing Pages';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'headline' => 'Headline',
            'zone' => 'Zone',
            'is_active' => 'Active',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors AreaLandingPageController@index filters.
     */
    public function query(Request $request): Builder
    {
        return AreaLandingPage::with('zone:id,name,type')
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->latest();
    }

    /**
     * @param  AreaLandingPage  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'headline' => $row->headline,
            'zone' => $row->zone?->name,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
