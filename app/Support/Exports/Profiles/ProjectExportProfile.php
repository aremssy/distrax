<?php

namespace App\Support\Exports\Profiles;

use App\Models\Project;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProjectExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'projects';
    }

    public function permission(): string
    {
        return 'projects.view';
    }

    public function title(): string
    {
        return 'Projects';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'developer_name' => 'Developer',
            'category' => 'Category',
            'zone' => 'Zone',
            'price_from' => 'Price From',
            'status' => 'Status',
            'is_featured' => 'Featured',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ProjectController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Project::query()
            ->with(['zone:id,name', 'category:id,name'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('project_category_id', $categoryId))
            ->orderBy('sort_order')
            ->latest();
    }

    /**
     * @param  Project  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'developer_name' => $row->developer_name,
            'category' => $row->category?->name,
            'zone' => $row->zone?->name,
            'price_from' => $row->price_from,
            'status' => $row->status,
            'is_featured' => $row->is_featured ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
