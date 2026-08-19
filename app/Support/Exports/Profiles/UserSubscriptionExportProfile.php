<?php

namespace App\Support\Exports\Profiles;

use App\Models\UserSubscription;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UserSubscriptionExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'subscriptions';
    }

    public function permission(): string
    {
        return 'plans.view';
    }

    public function title(): string
    {
        return 'User Subscriptions';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'user' => 'User',
            'plan' => 'Plan',
            'status' => 'Status',
            'starts_at' => 'Starts At',
            'ends_at' => 'Ends At',
            'posts_used' => 'Posts Used',
            'auto_renew' => 'Auto Renew',
            'created_at' => 'Date',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors UserSubscriptionController@index filters.
     */
    public function query(Request $request): Builder
    {
        return UserSubscription::with(['user:id,name,email', 'plan:id,name,post_limit'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest();
    }

    /**
     * @param  UserSubscription  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'user' => $row->user?->name,
            'plan' => $row->plan?->name,
            'status' => $row->status,
            'starts_at' => $row->starts_at?->toDateTimeString(),
            'ends_at' => $row->ends_at?->toDateTimeString(),
            'posts_used' => $row->posts_used,
            'auto_renew' => $row->auto_renew ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
