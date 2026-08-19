<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['invoice_number', 'user_id', 'invoiceable_type', 'invoiceable_id', 'type', 'amount', 'currency', 'status', 'notes', 'issued_at', 'paid_at'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceable(): MorphTo
    {
        return $this->morphTo();
    }
}
