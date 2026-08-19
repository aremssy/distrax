<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTechnicianCategoryRequest;
use App\Models\TechnicianCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TechnicianCategoryController extends Controller
{
    use ReordersRecords;

    public function index(): View
    {
        return view('admin.technician-categories.index', [
            'categories' => TechnicianCategory::orderBy('sort_order')->orderBy('name')->paginate(25),
        ]);
    }

    public function store(SaveTechnicianCategoryRequest $request): RedirectResponse
    {
        $data = $this->prepared($request);
        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = $this->nextSortOrder(TechnicianCategory::class);

        TechnicianCategory::create($data);

        return redirect()->route('admin.technician-categories.index')->with('success', 'Category created.');
    }

    public function update(SaveTechnicianCategoryRequest $request, TechnicianCategory $category): RedirectResponse
    {
        $data = $this->prepared($request, $category);
        $data['slug'] = Str::slug($data['name']);

        $category->update($data);

        return redirect()->route('admin.technician-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(TechnicianCategory $category): RedirectResponse
    {
        if ($category->technicians()->exists()) {
            return redirect()->route('admin.technician-categories.index')->with('error', 'Categories with technicians cannot be deleted. Deactivate instead.');
        }

        $category->delete();

        return redirect()->route('admin.technician-categories.index')->with('success', 'Category deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:technician_categories,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(TechnicianCategory::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(SaveTechnicianCategoryRequest $request, ?TechnicianCategory $category = null): array
    {
        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active', $category?->is_active ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
