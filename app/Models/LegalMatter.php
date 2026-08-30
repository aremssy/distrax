<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['deal_id', 'type', 'status', 'assigned_to', 'due_date', 'notes', 'resolved_at'])]
class LegalMatter extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
