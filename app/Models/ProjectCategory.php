<?php

namespace App\Models;

use Database\Factories\ProjectCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'image', 'is_active', 'sort_order'])]
class ProjectCategory extends Model
{
    /** @use HasFactory<ProjectCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    protected static function booted(): void
    {
        static::creating(function (ProjectCategory $category): void {
            if (empty($category->slug)) {
                $category->slug = self::uniqueSlug(Str::slug($category->name) ?: 'category');
            }
        });

        static::updating(function (ProjectCategory $category): void {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = self::uniqueSlug(Str::slug($category->name) ?: 'category', $category->id);
            }
        });
    }

    private static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $suffix = 1;

        while (self::where('slug', $slug)->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
