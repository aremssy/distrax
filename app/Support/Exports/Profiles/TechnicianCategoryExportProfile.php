<?php

namespace App\Support\Exports\Profiles;

use App\Models\TechnicianCategory;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TechnicianCategoryExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'technician-categories';
    }

    public function permission(): string
    {
        return 'technicians.view';
    }

    public function title(): string
    {
        return 'Technician Categories';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'commission_rate' => 'Commission Rate',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors TechnicianCategoryController@index ordering.
     */
    public function query(Request $request): Builder
    {
        return TechnicianCategory::orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  TechnicianCategory  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'commission_rate' => $row->commission_rate,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
