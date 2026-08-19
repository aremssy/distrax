<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_listing_id', 'name', 'floor', 'rent_amount', 'status', 'notes'])]
class Unit extends Model
{
    use HasFactory, SoftDeletes;

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }
}
