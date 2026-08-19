<?php

namespace App\Support\Exports\Profiles;

use App\Models\PropertyListing;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ListingsExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'listings';
    }

    public function permission(): string
    {
        return 'listings.view';
    }

    public function title(): string
    {
        return 'Listings';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'zone' => 'Zone',
            'type' => 'Type',
            'price' => 'Price',
            'status' => 'Status',
            'owner' => 'Published By',
            'published_at' => 'Published On',
            'created_at' => 'Created At',
        ];
    }

    public function perPage(): int
    {
        return 20;
    }

    /**
     * Mirrors ListingController@index / @export: the model filter scope plus the
     * trashed toggle, ordered newest-first.
     */
    public function query(Request $request): Builder
    {
        $query = PropertyListing::with(['zone:id,name', 'owner:id,name,email']);

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        return $query->filter($request)->latest();
    }

    /**
     * @param  PropertyListing  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'zone' => $row->zone?->name,
            'type' => $row->type,
            'price' => $row->price,
            'status' => $row->status,
            'owner' => $row->owner?->name,
            'published_at' => $row->published_at?->toDateString(),
            'created_at' => $row->created_at?->toDateString(),
        ];
    }
}
