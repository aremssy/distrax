<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['wallet_id', 'type', 'amount', 'balance_after', 'description', 'transactionable_type', 'transactionable_id'])]
class WalletTransaction extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
