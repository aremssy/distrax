<?php

namespace App\Support\Exports\Profiles;

use App\Models\ListingPackage;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ListingPackageExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'packages';
    }

    public function permission(): string
    {
        return 'listing_packages.view';
    }

    public function title(): string
    {
        return 'Listing Packages';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'price' => 'Price',
            'post_quota' => 'Post Quota',
            'duration_days' => 'Duration (Days)',
            'is_active' => 'Active',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ListingPackageController@index ordering.
     */
    public function query(Request $request): Builder
    {
        return ListingPackage::orderBy('sort_order')->orderBy('price');
    }

    /**
     * @param  ListingPackage  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'price' => $row->price,
            'post_quota' => $row->post_quota,
            'duration_days' => $row->duration_days,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
