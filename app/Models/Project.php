<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['zone_id', 'project_category_id', 'developer_name', 'title', 'slug', 'description', 'price_from', 'status', 'cover_image', 'completion_date', 'is_featured', 'sort_order'])]
class Project extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_from' => 'integer',
            'description' => SanitizedHtml::class,
            'completion_date' => 'date',
            'is_featured' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (empty($project->slug)) {
                $project->slug = self::uniqueSlug(Str::slug($project->title));
            }
        });

        static::updating(function (Project $project): void {
            if ($project->isDirty('title') && ! $project->isDirty('slug')) {
                $project->slug = self::uniqueSlug(Str::slug($project->title), $project->id);
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
}
