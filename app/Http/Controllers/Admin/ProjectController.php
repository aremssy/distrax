<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProjectRequest;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->with(['zone:id,name', 'category:id,name'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('project_category_id', $categoryId))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(25);

        $zones = Zone::active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name']);
        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('admin.projects.index', compact('projects', 'zones', 'categories'));
    }

    public function store(SaveProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] ??= Str::slug($data['title']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['cover_image'] = $request->file('cover_image')?->store('projects', 'public');

        Project::create($data);

        return redirect()->route('admin.projects.index')->with('success', 'Project created.');
    }

    public function show(Project $project): View
    {
        $categories = ProjectCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($project->project_category_id, fn ($query, int $categoryId) => $query->orWhere('id', $categoryId)))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.projects.show', [
            'project' => $project->load(['zone:id,name', 'category:id,name']),
            'zones' => Zone::active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name']),
            'categories' => $categories,
        ]);
    }

    public function update(SaveProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        $data['slug'] ??= Str::slug($data['title']);
        $data['sort_order'] = $data['sort_order'] ?? $project->sort_order;
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('cover_image')) {
            $oldImage = $project->cover_image;
            $data['cover_image'] = $request->file('cover_image')->store('projects', 'public');
        } else {
            unset($data['cover_image']);
        }

        $project->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }
}
