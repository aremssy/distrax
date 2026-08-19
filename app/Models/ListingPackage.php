<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'slug', 'price', 'post_quota', 'duration_days', 'features', 'is_active', 'sort_order'])]
class ListingPackage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
