<?php

namespace App\Support\Exports\Profiles;

use App\Models\Agency;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AgencyExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'agencies';
    }

    public function permission(): string
    {
        return 'agencies.view';
    }

    public function title(): string
    {
        return 'Agencies';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'owner' => 'Owner',
            'phone' => 'Phone',
            'email' => 'Email',
            'agents_count' => 'Agents',
            'listings_count' => 'Listings',
            'is_verified' => 'Verified',
            'status' => 'Status',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 20;
    }

    /**
     * Mirrors AgencyController@index filters.
     */
    public function query(Request $request): Builder
    {
        $search = $request->string('search');

        return Agency::with('owner:id,name,email')
            ->withCount(['agents', 'listings'])
            ->when($search->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest();
    }

    /**
     * @param  Agency  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'owner' => $row->owner?->name,
            'phone' => $row->phone,
            'email' => $row->email,
            'agents_count' => $row->agents_count,
            'listings_count' => $row->listings_count,
            'is_verified' => $row->is_verified ? 'Yes' : 'No',
            'status' => $row->status,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
