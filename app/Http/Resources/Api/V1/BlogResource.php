<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Blog
 */
class BlogResource extends JsonResource
{
    /** Whether to include the full post body (detail view only). */
    protected bool $withContent = false;

    /** Fluently opt into serializing the full HTML content. */
    public function withContent(bool $value = true): static
    {
        $this->withContent = $value;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'featured_image' => $this->featuredImageUrl(),
            'tags' => $this->tags ?? [],
            'read_minutes' => $this->readMinutes(),
            'view_count' => (int) $this->view_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'author' => $this->whenLoaded('author', fn (): ?array => $this->author ? [
                'name' => $this->author->name,
                'avatar' => $this->author->avatar
                    ? asset('storage/'.$this->author->avatar)
                    : null,
            ] : null),
            ...($this->withContent ? ['content' => (string) $this->content] : []),
        ];
    }
}
