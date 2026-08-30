<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['offer_id', 'sender_id', 'amount', 'message', 'status'])]
class Negotiation extends Model
{
    use HasFactory;
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
