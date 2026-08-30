<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['verification_case_id', 'layer', 'owner_role', 'status', 'assigned_to', 'notes', 'completed_at'])]
class VerificationTask extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(VerificationCase::class, 'verification_case_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(VerificationEvidence::class);
    }
}
