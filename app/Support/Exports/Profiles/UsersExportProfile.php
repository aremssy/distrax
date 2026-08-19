<?php

namespace App\Support\Exports\Profiles;

use App\Models\User;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UsersExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'users';
    }

    public function permission(): string
    {
        return 'users.view';
    }

    public function title(): string
    {
        return 'Users';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'role' => 'Role',
            'verification_status' => 'Verification',
            'is_blocked' => 'Blocked',
            'created_at' => 'Registered',
        ];
    }

    public function perPage(): int
    {
        return 20;
    }

    /**
     * Mirrors UserController@index so an export respects the same filters as the listing.
     */
    public function query(Request $request): Builder
    {
        $search = $request->string('search');

        return User::with('role')
            ->when($search->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($request->integer('role_id'), fn ($q, $id) => $q->where('role_id', $id))
            ->when($request->string('verification_status')->isNotEmpty(), fn ($q) => $q->where('verification_status', $request->string('verification_status')->value()))
            ->when($request->string('is_blocked')->isNotEmpty(), fn ($q) => $q->where('is_blocked', $request->string('is_blocked')->value() === '1'))
            ->latest();
    }

    /**
     * @param  User  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'phone' => $row->phone,
            'role' => $row->role?->display_name ?? $row->role?->name,
            'verification_status' => $row->verification_status,
            'is_blocked' => $row->is_blocked ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
