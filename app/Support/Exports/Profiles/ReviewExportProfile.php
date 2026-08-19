<?php

namespace App\Support\Exports\Profiles;

use App\Models\Review;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReviewExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'reviews';
    }

    public function permission(): string
    {
        return 'reviews.view';
    }

    public function title(): string
    {
        return 'Reviews';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'reviewer' => 'Reviewer',
            'rating' => 'Rating',
            'body' => 'Review',
            'is_verified' => 'Verified',
            'is_visible' => 'Visible',
            'moderator' => 'Moderated By',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ReviewModerationController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Review::with(['reviewer:id,name,email', 'reviewable', 'moderator:id,name,email'])
            ->when($request->filled('visible'), fn ($query) => $query->where('is_visible', $request->boolean('visible')))
            ->when($request->filled('verified'), fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('reviewer', fn ($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ))
            ->latest();
    }

    /**
     * @param  Review  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'reviewer' => $row->reviewer?->name,
            'rating' => $row->rating,
            'body' => $row->body,
            'is_verified' => $row->is_verified ? 'Yes' : 'No',
            'is_visible' => $row->is_visible ? 'Yes' : 'No',
            'moderator' => $row->moderator?->name,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
