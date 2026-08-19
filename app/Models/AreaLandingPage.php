<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['zone_id', 'headline', 'subheadline', 'content', 'featured_image', 'features', 'is_active'])]
class AreaLandingPage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            // Rendered raw on the public landing page, so sanitize on write.
            'content' => SanitizedHtml::class,
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
