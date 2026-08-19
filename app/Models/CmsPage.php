<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['created_by', 'title', 'slug', 'content', 'meta_title', 'meta_description', 'featured_image', 'status', 'published_at'])]
class CmsPage extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'content' => SanitizedHtml::class,
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Headings used to build the on-page table of contents.
     *
     * @return array<int, array{id: string, title: string}>
     */
    public function tableOfContents(): array
    {
        preg_match_all('/<h2\b[^>]*>(.*?)<\/h2>/is', (string) $this->content, $matches);

        return collect($matches[1])
            ->map(fn (string $heading): string => trim(html_entity_decode(strip_tags($heading))))
            ->filter()
            ->map(fn (string $title): array => ['id' => $this->headingAnchor($title), 'title' => $title])
            ->unique('id')
            ->values()
            ->all();
    }

    /** The page content with an anchor id added to every H2 so the contents list can link to it. */
    public function contentWithAnchors(): string
    {
        return (string) preg_replace_callback(
            '/<h2\b([^>]*)>(.*?)<\/h2>/is',
            function (array $match): string {
                [$full, $attributes, $inner] = $match;

                if (str_contains($attributes, 'id=')) {
                    return $full;
                }

                $anchor = $this->headingAnchor(trim(html_entity_decode(strip_tags($inner))));

                return '<h2'.$attributes.' id="'.e($anchor).'">'.$inner.'</h2>';
            },
            (string) $this->content,
        );
    }

    /** Anchors ignore any leading "3." numbering so renumbering a section keeps its link stable. */
    private function headingAnchor(string $title): string
    {
        return Str::slug(preg_replace('/^\s*\d+\.\s*/', '', $title) ?: $title);
    }
}
