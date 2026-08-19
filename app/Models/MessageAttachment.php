<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['message_id', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class MessageAttachment extends Model
{
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isImage(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'image/');
    }

    public function getUrlAttribute(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
