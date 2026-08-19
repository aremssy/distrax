<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['seoable_type', 'seoable_id', 'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'additional_meta'])]
class SeoMeta extends Model
{
    /**
     * Content types SEO meta can attach to, mapped to the column used as a
     * human-readable label when listing their records. Single source of truth
     * for the admin type filter, the create form and the records endpoint.
     *
     * @var array<class-string<Model>, string>
     */
    public const SEOABLE_TYPES = [
        CmsPage::class => 'title',
        Blog::class => 'title',
        PropertyListing::class => 'title',
        Zone::class => 'name',
    ];

    protected $table = 'seo_meta';

    protected function casts(): array
    {
        return [
            'additional_meta' => 'array',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
