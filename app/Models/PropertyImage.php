<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['property_listing_id', 'path', 'disk', 'is_cover', 'is_floor_plan', 'sort_order'])]
class PropertyImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'is_floor_plan' => 'boolean',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    /**
     * Public URL for the image. Remote images (the `remote` disk, or any absolute URL)
     * are returned as-is; local images resolve through their storage disk. This avoids
     * calling Storage::disk('remote'), which has no configured driver.
     */
    public function url(): string
    {
        if ($this->disk === 'remote' || Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
