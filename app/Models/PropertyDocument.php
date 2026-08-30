<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['documentable_type', 'documentable_id', 'uploaded_by', 'type', 'file_path', 'is_verified', 'visibility_level', 'metadata'])]
class PropertyDocument extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function filename(): ?string
    {
        return $this->file_path ? basename((string) $this->file_path) : null;
    }

    public function url(): string
    {
        $path = (string) $this->file_path;

        return str_starts_with($path, 'http')
            ? $path
            : (string) \Illuminate\Support\Facades\Storage::disk('local')->url($path);
    }
}
