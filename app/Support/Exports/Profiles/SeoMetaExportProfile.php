<?php

namespace App\Support\Exports\Profiles;

use App\Models\SeoMeta;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SeoMetaExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'seo';
    }

    public function permission(): string
    {
        return 'seo.view';
    }

    public function title(): string
    {
        return 'SEO Meta';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'seoable_type' => 'Type',
            'seoable_id' => 'Record',
            'meta_title' => 'Meta Title',
            'og_title' => 'OG Title',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors SeoMetaController@index filters. The polymorphic relation is
     * intentionally not eager-loaded, matching the listing.
     */
    public function query(Request $request): Builder
    {
        return SeoMeta::query()
            ->when($request->string('type')->value(), fn ($query, string $type) => $query->where('seoable_type', $type))
            ->latest();
    }

    /**
     * @param  SeoMeta  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'seoable_type' => $row->seoable_type ? class_basename((string) $row->seoable_type) : null,
            'seoable_id' => $row->seoable_id,
            'meta_title' => $row->meta_title,
            'og_title' => $row->og_title,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
