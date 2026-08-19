<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $planLimit = $this->plan?->post_limit === 0 ? null : (int) ($this->plan?->post_limit ?? 0);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'auto_renew' => $this->auto_renew,

            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'slug' => $this->plan->slug,
                'price' => (int) $this->plan->price,
                'currency' => setting('default_currency', 'BDT'),
                'duration_days' => (int) $this->plan->duration_days,
                'post_limit' => $planLimit,
                'post_limit_label' => $planLimit === null ? 'Unlimited' : (string) $planLimit,
                'unlocked_features' => $this->plan->unlocked_features ?? [],
            ]),

            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),

            'posts_used' => (int) $this->posts_used,
            'posts_limit' => $planLimit,

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
