<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institutional_account_id', 'uploaded_by', 'original_filename', 'status', 'total_rows', 'processed_rows', 'created_count', 'failed_count', 'error_report'])]
class BulkUploadBatch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'error_report' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(InstitutionalAccount::class, 'institutional_account_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
