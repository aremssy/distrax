<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'owner_id', 'property_listing_id', 'unit_id', 'tenant_id',
    'tenant_name', 'tenant_phone', 'tenant_email',
    'rent_amount', 'deposit_amount', 'due_day_of_month',
    'start_date', 'end_date', 'status',
])]
class Tenancy extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function rentPayments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function tenantName(): string
    {
        return $this->tenant?->name ?? $this->tenant_name ?? 'Unnamed tenant';
    }
}
