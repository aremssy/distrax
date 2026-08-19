<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rent_payment_id', 'tenancy_id', 'receipt_number', 'amount', 'issued_at', 'notes'])]
class RentReceipt extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function rentPayment(): BelongsTo
    {
        return $this->belongsTo(RentPayment::class);
    }

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }
}
