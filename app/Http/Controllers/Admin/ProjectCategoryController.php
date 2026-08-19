<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProjectCategoryRequest;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectCategoryController extends Controller
{
    use ReordersRecords;

    public function index(): View
    {
        return view('admin.projects.categories.index', [
            'categories' => ProjectCategory::withCount('projects')->orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function store(SaveProjectCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $this->nextSortOrder(ProjectCategory::class);
        $data['image'] = $request->file('image')?->store('project-categories', 'public');

        ProjectCategory::create($data);

        return redirect()->route('admin.projects.categories.index')->with('success', 'Category created.');
    }

    public function update(SaveProjectCategoryRequest $request, ProjectCategory $projectCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $projectCategory->is_active);
        $data['sort_order'] = $data['sort_order'] ?? $projectCategory->sort_order;

        if ($request->hasFile('image')) {
            $oldImage = $projectCategory->image;
            $data['image'] = $request->file('image')->store('project-categories', 'public');
        } else {
            unset($data['image']);
        }

        $projectCategory->update($data);

        if (isset($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.projects.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ProjectCategory $projectCategory): RedirectResponse
    {
        if ($projectCategory->projects()->exists()) {
            return redirect()->route('admin.projects.categories.index')->with('error', 'Categories with projects cannot be deleted.');
        }

        $image = $projectCategory->image;
        $projectCategory->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->route('admin.projects.categories.index')->with('success', 'Category deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:project_categories,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(ProjectCategory::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
