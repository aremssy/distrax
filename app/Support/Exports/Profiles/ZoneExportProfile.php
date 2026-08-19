<?php

namespace App\Support\Exports\Profiles;

use App\Models\Zone;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ZoneExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'zones';
    }

    public function permission(): string
    {
        return 'zones.view';
    }

    public function title(): string
    {
        return 'Zones';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'type' => 'Type',
            'parent' => 'Parent',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 50;
    }

    /**
     * Mirrors ZoneController@index's paginated Zone query with its search/type filters
     * and ordering. The pure tree-building branch of the controller is intentionally
     * not reproduced — the export is a flat list of every matching zone.
     */
    public function query(Request $request): Builder
    {
        $search = $request->string('search');
        $type = $request->string('type');

        return Zone::with('parent')
            ->when($search->isNotEmpty(), fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($type->isNotEmpty(), fn ($q) => $q->where('type', $type->value()))
            ->orderBy('type')
            ->orderBy('name');
    }

    /**
     * @param  Zone  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'type' => $row->type,
            'parent' => $row->parent?->name,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
