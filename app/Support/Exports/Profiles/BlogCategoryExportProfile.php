<?php

namespace App\Support\Exports\Profiles;

use App\Models\BlogCategory;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BlogCategoryExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'blog-categories';
    }

    public function permission(): string
    {
        return 'blogs.view';
    }

    public function title(): string
    {
        return 'Blog Categories';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'slug' => 'Slug',
            'blogs_count' => 'Posts',
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
     * Mirrors BlogCategoryController@index.
     */
    public function query(Request $request): Builder
    {
        return BlogCategory::withCount('blogs')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * @param  BlogCategory  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'blogs_count' => $row->blogs_count,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
