<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'legal_entity_name', 'registration_number', 'country_code', 'address', 'status', 'is_verified'])]
class InstitutionalAccount extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BulkUploadBatch::class);
    }
}
