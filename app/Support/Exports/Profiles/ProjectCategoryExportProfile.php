<?php

namespace App\Support\Exports\Profiles;

use App\Models\ProjectCategory;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProjectCategoryExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'project-categories';
    }

    public function permission(): string
    {
        return 'projects.view';
    }

    public function title(): string
    {
        return 'Project Categories';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'projects_count' => 'Projects',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ProjectCategoryController@index.
     */
    public function query(Request $request): Builder
    {
        return ProjectCategory::withCount('projects')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * @param  ProjectCategory  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'projects_count' => $row->projects_count,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
