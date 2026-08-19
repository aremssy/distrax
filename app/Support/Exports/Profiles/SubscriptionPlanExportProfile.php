<?php

namespace App\Support\Exports\Profiles;

use App\Models\SubscriptionPlan;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SubscriptionPlanExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'plans';
    }

    public function permission(): string
    {
        return 'plans.view';
    }

    public function title(): string
    {
        return 'Subscription Plans';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'price' => 'Price',
            'duration_days' => 'Duration (Days)',
            'post_limit' => 'Post Limit',
            'is_featured' => 'Featured',
            'is_active' => 'Active',
            'user_subscriptions_count' => 'Subscriptions',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors SubscriptionPlanController@index ordering.
     */
    public function query(Request $request): Builder
    {
        return SubscriptionPlan::withCount('userSubscriptions')
            ->orderBy('sort_order')
            ->orderBy('price');
    }

    /**
     * @param  SubscriptionPlan  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'price' => $row->price,
            'duration_days' => $row->duration_days,
            'post_limit' => $row->post_limit,
            'is_featured' => $row->is_featured ? 'Yes' : 'No',
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'user_subscriptions_count' => $row->user_subscriptions_count,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
