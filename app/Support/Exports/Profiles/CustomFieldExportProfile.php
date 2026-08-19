<?php

namespace App\Support\Exports\Profiles;

use App\Enums\PropertyType;
use App\Models\CustomField;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CustomFieldExportProfile extends AbstractExportProfile
{
    /** @var list<string> */
    public function key(): string
    {
        return 'custom-fields';
    }

    public function permission(): string
    {
        return 'listings.edit';
    }

    public function title(): string
    {
        return 'Custom Fields';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'label' => 'Label',
            'key' => 'Key',
            'property_type' => 'Property Type',
            'type' => 'Field Type',
            'is_required' => 'Required',
            'is_filterable' => 'Filterable',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    /**
     * Mirrors CustomFieldController@index — resolves the selected property type the
     * same way (falling back to "rent") and applies the forType scope and ordering.
     */
    public function query(Request $request): Builder
    {
        $selectedType = $request->string('type', 'rent')->value();
        if (! in_array($selectedType, PropertyType::values())) {
            $selectedType = 'rent';
        }

        return CustomField::forType($selectedType)
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /**
     * @param  CustomField  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'label' => $row->label,
            'key' => $row->key,
            'property_type' => $row->property_type,
            'type' => $row->type,
            'is_required' => $row->is_required ? 'Yes' : 'No',
            'is_filterable' => $row->is_filterable ? 'Yes' : 'No',
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
