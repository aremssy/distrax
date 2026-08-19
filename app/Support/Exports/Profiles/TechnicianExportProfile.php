<?php

namespace App\Support\Exports\Profiles;

use App\Models\Technician;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TechnicianExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'technicians';
    }

    public function permission(): string
    {
        return 'technicians.view';
    }

    public function title(): string
    {
        return 'Technicians';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'category' => 'Category',
            'zone' => 'Zone',
            'status' => 'Status',
            'hourly_rate' => 'Hourly Rate',
            'rating' => 'Rating',
            'is_verified' => 'Verified',
            'is_available' => 'Available',
            'created_at' => 'Joined',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors TechnicianController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Technician::with(['user:id,name,email,phone', 'category:id,name', 'zone:id,name'])
            ->withAvg('reviews', 'rating')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('technician_category_id', $categoryId))
            ->latest();
    }

    /**
     * @param  Technician  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->user?->name,
            'email' => $row->user?->email,
            'phone' => $row->user?->phone,
            'category' => $row->category?->name,
            'zone' => $row->zone?->name,
            'status' => $row->status,
            'hourly_rate' => $row->hourly_rate,
            'rating' => $row->rating,
            'is_verified' => $row->is_verified ? 'Yes' : 'No',
            'is_available' => $row->is_available ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
