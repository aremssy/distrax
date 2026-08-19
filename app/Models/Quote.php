<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['technician_booking_id', 'technician_id', 'amount', 'currency_code', 'description', 'valid_until', 'status'])]
class Quote extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TechnicianBooking::class, 'technician_booking_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
