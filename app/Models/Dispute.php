<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['raised_by', 'against_user_id', 'property_listing_id', 'disputable_type', 'disputable_id', 'payment_id', 'refund_id', 'subject', 'description', 'status', 'assigned_to', 'resolution', 'outcome', 'resolved_at'])]
class Dispute extends Model
{
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function againstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function disputable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }
}
