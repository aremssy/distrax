<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with(['zone:id,name', 'category:id,name,image'])->orderByDesc('is_featured')->orderBy('sort_order')->paginate(12);

        return view('website.pages.projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $project->load(['zone:id,name', 'category:id,name,image']);

        return view('website.pages.projects.show', compact('project'));
    }
}
