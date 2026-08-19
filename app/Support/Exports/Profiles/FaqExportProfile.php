<?php

namespace App\Support\Exports\Profiles;

use App\Models\Faq;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FaqExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'faqs';
    }

    public function permission(): string
    {
        return 'cms.view';
    }

    public function title(): string
    {
        return 'FAQs';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'question' => 'Question',
            'category' => 'Category',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 50;
    }

    /**
     * Mirrors FaqController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Faq::when($request->string('category')->value(), fn ($query, string $category) => $query->where('category', $category))
            ->ordered();
    }

    /**
     * @param  Faq  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'question' => $row->question,
            'category' => $row->category,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
