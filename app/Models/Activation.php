<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['purchase_code', 'envato_item_id', 'license_type', 'buyer_email', 'buyer_name', 'verified_at', 'expires_at', 'payload'])]
class Activation extends Model
{
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public static function isActivated(): bool
    {
        return static::whereNotNull('verified_at')->exists();
    }
}
