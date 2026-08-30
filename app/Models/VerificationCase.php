<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['property_listing_id', 'status', 'assigned_officer_id', 'opened_at', 'closed_at', 'expiry_review_date', 'notes'])]
class VerificationCase extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'expiry_review_date' => 'date',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(VerificationTask::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(VerificationScore::class);
    }
}
