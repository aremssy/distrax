<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['author_id', 'blog_category_id', 'title', 'slug', 'content', 'excerpt', 'featured_image', 'tags', 'meta_title', 'meta_description', 'status', 'sort_order', 'published_at'])]
class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'content' => SanitizedHtml::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Resolved URL for the featured image, with a bundled fallback. */
    public function featuredImageUrl(): string
    {
        return match (true) {
            ! $this->featured_image => asset('assets/images/website/features/flat-1.webp'),
            str_starts_with($this->featured_image, 'http') => $this->featured_image,
            default => asset('storage/'.$this->featured_image),
        };
    }

    /** Estimated reading time in whole minutes, based on 200 words per minute. */
    public function readMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }
}
