<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'property_listing_id', 'booked_by', 'visit_schedule_id', 'inspector_id', 'type', 'scheduled_at',
    'status', 'checklist', 'gps_lat', 'gps_lng', 'geodata', 'summary',
    'report_url', 'issues', 'buyer_acknowledged_at', 'completed_at',
])]
class Inspection extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'geodata' => 'array',
            'scheduled_at' => 'datetime',
            'buyer_acknowledged_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function visitSchedule(): BelongsTo
    {
        return $this->belongsTo(VisitSchedule::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(InspectionEvidence::class);
    }
}
