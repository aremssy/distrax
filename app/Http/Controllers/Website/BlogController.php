<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Blog::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug'])
            ->when($request->filled('category'), fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category'))))
            ->when($request->filled('tag'), fn ($query) => $query->whereJsonContains('tags', (string) $request->string('tag')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::query()
            ->where('is_active', true)
            ->withCount(['blogs' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        $popularPosts = Blog::published()
            ->with('category:id,name,slug')
            ->orderByDesc('view_count')
            ->limit(3)
            ->get();

        $popularTags = Blog::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->keys();

        // The hero treatment only applies to the unfiltered first page of the feed.
        $showFeatured = $posts->onFirstPage() && ! $request->hasAny(['category', 'tag', 'q']);

        // Live search swaps only the results column, so skip the full layout.
        if ($request->ajax()) {
            return view('website.blog._results', compact('posts', 'showFeatured'));
        }

        return view('website.blog.index', compact('posts', 'categories', 'popularPosts', 'popularTags', 'showFeatured'));
    }

    public function show(string $slug): View
    {
        $post = Blog::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('view_count');

        $relatedPosts = Blog::published()
            ->with('category:id,name,slug')
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('website.blog.show', compact('post', 'relatedPosts'));
    }
}
