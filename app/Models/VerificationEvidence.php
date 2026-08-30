<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['verification_task_id', 'type', 'file_path', 'description', 'uploaded_by', 'metadata'])]
class VerificationEvidence extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(VerificationTask::class, 'verification_task_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
