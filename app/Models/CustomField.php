<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['property_type', 'label', 'key', 'type', 'options', 'is_required', 'is_filterable', 'is_active', 'sort_order'])]
class CustomField extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /** Fields for one property type, plus the `all` fields that apply to every type. */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->whereIn('property_type', [$type, 'all']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect', 'radio', 'checkbox']);
    }
}
