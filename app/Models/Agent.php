<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['agency_id', 'user_id', 'slug', 'title', 'bio', 'phone', 'whatsapp', 'is_active'])]
class Agent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent): void {
            if (empty($agent->slug)) {
                $name = User::whereKey($agent->user_id)->value('name');
                $agent->slug = self::uniqueSlug(Str::slug((string) $name) ?: 'agent');
            }
        });
    }

    private static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 1;

        while (self::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(PropertyListing::class, 'owner_id', 'user_id');
    }
}
