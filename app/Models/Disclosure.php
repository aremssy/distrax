<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['property_listing_id', 'disclosed_by', 'category', 'description', 'document_path', 'acknowledged_at'])]
class Disclosure extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function discloser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disclosed_by');
    }
}
