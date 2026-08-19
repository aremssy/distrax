<?php

namespace App\Support\Exports\Profiles;

use App\Models\Blog;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BlogExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'blogs';
    }

    public function permission(): string
    {
        return 'blogs.view';
    }

    public function title(): string
    {
        return 'Blog Posts';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'category' => 'Category',
            'author' => 'Author',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors BlogController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Blog::with(['author:id,name,email', 'category:id,name'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('blog_category_id', $categoryId))
            ->orderBy('sort_order')
            ->latest();
    }

    /**
     * @param  Blog  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'category' => $row->category?->name,
            'author' => $row->author?->name,
            'status' => $row->status,
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
