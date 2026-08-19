<?php

namespace App\Support\Exports\Profiles;

use App\Models\CmsPage;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CmsPageExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'cms';
    }

    public function permission(): string
    {
        return 'cms.view';
    }

    public function title(): string
    {
        return 'CMS Pages';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'slug' => 'Slug',
            'creator' => 'Created By',
            'status' => 'Status',
            'published_at' => 'Published',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors CmsPageController@index filters.
     */
    public function query(Request $request): Builder
    {
        return CmsPage::with('creator:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest();
    }

    /**
     * @param  CmsPage  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'slug' => $row->slug,
            'creator' => $row->creator?->name,
            'status' => $row->status,
            'published_at' => $row->published_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
