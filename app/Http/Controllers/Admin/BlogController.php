<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BlogController extends Controller
{
    use ReordersRecords;

    public function index(Request $request): View
    {
        $posts = Blog::with(['author:id,name,email', 'category:id,name'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('blog_category_id', $categoryId))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(25);

        $categories = BlogCategory::orderBy('name')->get(['id', 'name']);

        return view('admin.blogs.index', compact('posts', 'categories'));
    }

    public function store(StoreBlogRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] ??= UniqueSlug::make(Blog::class, $data['title'], 'post');
        $data['author_id'] = auth()->id();
        $data['tags'] = array_values(array_unique($data['tags'] ?? []));
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['featured_image'] = $request->file('featured_image')?->store('blogs', 'public');
        $data['sort_order'] = $this->nextSortOrder(Blog::class);

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Post created.');
    }

    public function show(Blog $blog): View
    {
        return view('admin.blogs.show', [
            'post' => $blog->load(['author:id,name,email', 'category:id,name', 'seoMeta']),
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateBlogRequest $request, Blog $blog): RedirectResponse
    {
        // The edit page splits into independent Content / Settings / SEO forms, so
        // each request carries only its own fields; touch only what was submitted.
        $data = $request->validated();

        if (isset($data['title'])) {
            $data['slug'] ??= UniqueSlug::make(Blog::class, $data['title'], 'post', $blog->id);
        }

        if (array_key_exists('tags', $data)) {
            $data['tags'] = array_values(array_unique($data['tags'] ?? []));
        }

        if (isset($data['status'])) {
            $data['published_at'] = $data['status'] === 'published' && ! $blog->published_at ? now() : $blog->published_at;
        }

        if ($request->hasFile('featured_image')) {
            $oldImage = $blog->featured_image;
            $data['featured_image'] = $request->file('featured_image')->store('blogs', 'public');
        } else {
            unset($data['featured_image']);
        }

        $blog->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.blogs.show', $blog)->with('success', 'Post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('success', 'Post deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:blogs,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(Blog::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
