<?php

namespace App\Support\Exports\Profiles;

use App\Models\User;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class VerificationExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'verification';
    }

    public function permission(): string
    {
        return 'users.approve';
    }

    public function title(): string
    {
        return 'User Verification';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'verification_status' => 'Status',
            'reviewer' => 'Reviewed By',
            'verification_reviewed_at' => 'Reviewed At',
            'created_at' => 'Registered',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors VerificationController@index filters.
     */
    public function query(Request $request): Builder
    {
        return User::with('verificationReviewer:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('verification_status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->whereIn('verification_status', ['pending', 'verified', 'rejected'])
            ->latest('verification_reviewed_at');
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
            'verification_status' => $row->verification_status,
            'reviewer' => $row->verificationReviewer?->name,
            'verification_reviewed_at' => $row->verification_reviewed_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
