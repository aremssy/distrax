<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Services\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    use ReordersRecords;

    public function index(): View
    {
        return view('admin.blogs.categories.index', [
            'categories' => BlogCategory::withCount('blogs')->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = UniqueSlug::make(BlogCategory::class, $data['name'], 'category');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $this->nextSortOrder(BlogCategory::class);

        BlogCategory::create($data);

        return redirect()->route('admin.blogs.categories.index')->with('success', 'Category created.');
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] = UniqueSlug::make(BlogCategory::class, $data['name'], 'category', $blogCategory->id);
        $data['is_active'] = $request->boolean('is_active', $blogCategory->is_active);
        $data['sort_order'] = $data['sort_order'] ?? $blogCategory->sort_order;

        $blogCategory->update($data);

        return redirect()->route('admin.blogs.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->blogs()->exists()) {
            return redirect()->route('admin.blogs.categories.index')->with('error', 'Categories with blog posts cannot be deleted.');
        }

        $blogCategory->delete();

        return redirect()->route('admin.blogs.categories.index')->with('success', 'Category deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:blog_categories,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(BlogCategory::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
