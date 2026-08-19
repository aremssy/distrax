<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['tenancy_id', 'period_start', 'period_end', 'due_date', 'amount', 'currency_code', 'status', 'paid_at'])]
class RentPayment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(RentReceipt::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
