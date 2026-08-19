<?php

namespace App\Support\Exports\Profiles;

use App\Models\Testimonial;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TestimonialExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'testimonials';
    }

    public function permission(): string
    {
        return 'testimonials.view';
    }

    public function title(): string
    {
        return 'Testimonials';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'role' => 'Role',
            'quote' => 'Quote',
            'rating' => 'Rating',
            'is_featured' => 'Featured',
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
     * Mirrors TestimonialController@index filters and ordering.
     */
    public function query(Request $request): Builder
    {
        return Testimonial::query()
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->latest();
    }

    /**
     * @param  Testimonial  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'role' => $row->role,
            'quote' => $row->quote,
            'rating' => $row->rating,
            'is_featured' => $row->is_featured ? 'Yes' : 'No',
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'sort_order' => $row->sort_order,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
