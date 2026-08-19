<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtendUserSubscriptionRequest;
use App\Http\Requests\Admin\RefundUserSubscriptionRequest;
use App\Http\Requests\Admin\StoreUserSubscriptionRequest;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\SubscriptionRefunder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = UserSubscription::with(['user:id,name,email', 'plan:id,name,post_limit'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function store(StoreUserSubscriptionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $startsAt = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : now();

        UserSubscription::create([
            'user_id' => $data['user_id'],
            'subscription_plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays($plan->duration_days),
            'status' => 'active',
            'auto_renew' => $request->boolean('auto_renew'),
        ]);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    public function extend(ExtendUserSubscriptionRequest $request, UserSubscription $subscription): RedirectResponse
    {
        $data = $request->validated();

        $base = $subscription->ends_at && $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
        $subscription->update([
            'ends_at' => $base->copy()->addDays($data['days']),
            'status' => 'active',
        ]);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription extended successfully.');
    }

    public function cancel(UserSubscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription cancelled.');
    }

    public function refund(RefundUserSubscriptionRequest $request, UserSubscription $subscription, SubscriptionRefunder $refunder): RedirectResponse
    {
        $refunder->refund($subscription, $request->validated());

        return redirect()->route('admin.subscriptions.index')->with('success', 'Refund processed successfully.');
    }
}
