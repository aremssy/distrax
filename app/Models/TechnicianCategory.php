<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'icon', 'commission_rate', 'is_active', 'sort_order'])]
class TechnicianCategory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'commission_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }
}
