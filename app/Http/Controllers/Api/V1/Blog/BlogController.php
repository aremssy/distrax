<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\BlogCategoryResource;
use App\Http\Resources\Api\V1\BlogResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public blog endpoints for mobile/other clients. Mirrors the website blog:
 * a paginated, filterable feed, the category list, and a single post by slug.
 */
class BlogController extends ApiController
{
    /**
     * GET /api/v1/blog
     *
     * Paginated published posts. Filters: `category` (slug), `tag`, `q` (search).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['sometimes', 'string', 'max:120'],
            'tag' => ['sometimes', 'string', 'max:60'],
            'q' => ['sometimes', 'string', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $posts = Blog::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug'])
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $request->string('category'))
            ))
            ->when($request->filled('tag'), fn ($query) => $query->whereJsonContains(
                'tags',
                (string) $request->string('tag')
            ))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term));
            })
            ->latest('published_at')
            ->paginate((int) $request->integer('per_page', 9))
            ->withQueryString();

        return $this->paginated($posts, BlogResource::class);
    }

    /**
     * GET /api/v1/blog/categories
     *
     * Active categories with a count of their published posts.
     */
    public function categories(): JsonResponse
    {
        $categories = BlogCategory::query()
            ->where('is_active', true)
            ->withCount(['blogs' => fn ($query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success(BlogCategoryResource::collection($categories)->resolve());
    }

    /**
     * GET /api/v1/blog/{slug}
     *
     * A single published post (full body) plus related posts in the same
     * category. Increments the view counter.
     */
    public function show(string $slug): JsonResponse
    {
        $post = Blog::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug'])
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            return $this->error('Post not found.', 404);
        }

        $post->increment('view_count');

        $related = Blog::published()
            ->with('category:id,name,slug')
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        $data = (new BlogResource($post))->withContent()->resolve();
        $data['related'] = BlogResource::collection($related)->resolve();

        return $this->success($data);
    }
}
