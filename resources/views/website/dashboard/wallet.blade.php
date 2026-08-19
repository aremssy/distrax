@extends('website.layouts.dashboard')
@section('title', __('Wallet & Plans').' | '.config('app.name'))
@section('page-title', __('Wallet & Plans'))
@section('dashboard-content')
<div class="mx-auto max-w-6xl space-y-8">
    <div class="grid gap-6 lg:grid-cols-3"><section class="rounded-2xl bg-linear-to-br from-indigo-600 to-indigo-700 p-6 text-white shadow-sm"><p class="text-sm text-indigo-100">{{ __('Wallet balance') }}</p><p class="mt-3 text-3xl font-bold">{{ money($wallet->balance, $wallet->currency) }}</p><p class="mt-5 text-xs text-indigo-200">{{ __('Wallet payments activate immediately and are recorded atomically.') }}</p></section><section class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2"><div class="flex flex-col items-start justify-between gap-4 sm:flex-row"><div><p class="text-sm text-slate-500">{{ __('Current plan') }}</p><h2 class="mt-1 text-xl font-bold">{{ $currentSubscription?->plan?->name ?? __('Free plan') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $currentSubscription ? __('Active until :date', ['date' => $currentSubscription->ends_at->format('M j, Y')]) : __('Upgrade to unlock advanced rent-management tools.') }}</p></div>@if ($currentSubscription)<form action="{{ route('subscriptions.cancel', $currentSubscription) }}" method="POST">@csrf @method('DELETE')<button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-red-600">{{ __('Cancel') }}</button></form>@endif</div><div class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-3"><span class="block text-xs text-slate-500">{{ __('Posts used') }}</span>{{ $entitlement['used_posts'] }}</div><div class="rounded-lg bg-slate-50 p-3"><span class="block text-xs text-slate-500">{{ __('Remaining') }}</span>{{ $entitlement['remaining_posts'] ?? __('Unlimited') }}</div><div class="rounded-lg bg-slate-50 p-3"><span class="block text-xs text-slate-500">{{ __('Features') }}</span>{{ count($entitlement['unlocked_features']) }}</div></div></section></div>

    <section>
        <h2 class="mb-4 text-xl font-bold text-slate-950">{{ __('Subscription plans') }}</h2>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($plans as $plan)
                @php
                    // `unlocked_features` is stored either as a map of feature => enabled, or as
                    // a plain list of enabled feature names. Both shapes render as a checklist,
                    // with any disabled feature greyed out rather than hidden.
                    $planFeatures = collect($plan->unlocked_features ?? [])
                        ->map(fn ($value, $key) => is_bool($value) || is_int($value)
                            ? ['name' => (string) $key, 'enabled' => (bool) $value]
                            : ['name' => (string) $value, 'enabled' => true])
                        ->filter(fn (array $feature) => $feature['name'] !== '')
                        ->map(fn (array $feature) => [
                            'label' => str($feature['name'])->replace('_', ' ')->title()->value(),
                            'enabled' => $feature['enabled'],
                        ])
                        ->sortByDesc('enabled')
                        ->values();
                @endphp

                <article class="flex flex-col rounded-2xl border bg-white p-6 transition {{ $plan->is_featured ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-slate-200' }}">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-lg font-bold text-slate-950">{{ $plan->name }}</h3>
                        @if ($plan->is_featured)
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600">{{ __('Popular') }}</span>
                        @endif
                    </div>

                    <p class="mt-3 text-3xl font-bold text-slate-950">{{ money($plan->price) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('for :days days · :posts posts', ['days' => $plan->duration_days, 'posts' => $plan->post_limit ?: __('Unlimited')]) }}</p>

                    @if ($planFeatures->isNotEmpty())
                        <ul class="mt-5 space-y-2.5 text-sm">
                            @foreach ($planFeatures as $feature)
                                <li class="flex items-center gap-2 {{ $feature['enabled'] ? 'text-slate-700' : 'text-slate-500' }}">
                                    <i class="h-4 w-4 shrink-0 {{ $feature['enabled'] ? 'text-emerald-500' : 'text-slate-400' }}"
                                        data-lucide="{{ $feature['enabled'] ? 'circle-check' : 'circle-x' }}" aria-hidden="true"></i>
                                    {{ $feature['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! $currentSubscription)
                        <form class="mt-6 space-y-3" action="{{ route('subscriptions.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <label class="sr-only" for="coupon-{{ $plan->id }}">{{ __('Coupon code') }}</label>
                            <input id="coupon-{{ $plan->id }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm placeholder:text-slate-400"
                                name="coupon_code" placeholder="{{ __('Coupon code (optional)') }}">
                            <label class="sr-only" for="pay-{{ $plan->id }}">{{ __('Payment method') }}</label>
                            <select id="pay-{{ $plan->id }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="payment_method">
                                <option value="wallet">{{ __('Pay with wallet') }}</option>
                                @foreach ($activeGateways as $activeGateway)
                                    <option value="{{ $activeGateway }}">{{ __('Pay with :gateway', ['gateway' => str($activeGateway)->title()]) }}</option>
                                @endforeach
                            </select>
                            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Choose plan') }}</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-600">{{ __('No subscription plans are currently available.') }}</p>
            @endforelse
        </div>
    </section>

    <section><h2 class="mb-4 text-xl font-bold">{{ __('Extra listing packages') }}</h2><div class="grid gap-4 md:grid-cols-3">@forelse ($packages as $package)<article class="rounded-2xl border border-slate-200 bg-white p-5"><h3 class="font-bold">{{ $package->name }}</h3><p class="mt-2 text-2xl font-bold">{{ money($package->price) }}</p><p class="text-sm text-slate-500">{{ __(':quota extra posts · :days days', ['quota' => $package->post_quota, 'days' => $package->duration_days]) }}</p><form class="mt-4 flex gap-2" action="{{ route('listing-packages.store') }}" method="POST">@csrf<input type="hidden" name="package_id" value="{{ $package->id }}"><select class="min-w-0 flex-1 rounded-lg border border-slate-200 px-2 text-sm" name="payment_method"><option value="wallet">{{ __('Wallet') }}</option>@foreach ($activeGateways as $activeGateway)<option value="{{ $activeGateway }}">{{ str($activeGateway)->title() }}</option>@endforeach</select><button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Buy') }}</button></form></article>@empty<p class="text-sm text-slate-500">{{ __('No packages are currently available.') }}</p>@endforelse</div></section>

    <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6"><h2 class="font-bold text-indigo-950">{{ __('Advanced rent management') }}</h2>@if ($entitlement['unlocked_features'])<div class="mt-3 flex flex-wrap gap-2">@foreach ($entitlement['unlocked_features'] as $feature)<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700">{{ str($feature)->replace('_',' ')->title() }}</span>@endforeach</div>@else<p class="mt-2 text-sm text-indigo-800">{{ __('Rent reminders, receipts, income tracking, maintenance workflows, agreements, and multi-unit tools are locked. Choose an eligible plan to unlock them.') }}</p>@endif</section>

    <div class="grid gap-6 lg:grid-cols-2"><section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 font-bold">{{ __('Wallet transactions') }}</h2><div class="divide-y">@forelse ($wallet->transactions as $transaction)<div class="flex justify-between gap-4 px-5 py-3 text-sm"><div class="min-w-0"><p>{{ $transaction->description }}</p><time class="text-xs text-slate-500">{{ $transaction->created_at->format('M j, Y') }}</time></div><span class="shrink-0 font-semibold {{ $transaction->type === 'credit' ? 'text-emerald-600' : 'text-slate-900' }}">{{ $transaction->type === 'credit' ? '+' : '-' }}{{ money($transaction->amount, $wallet->currency) }}</span></div>@empty<p class="p-5 text-sm text-slate-500">{{ __('No wallet transactions.') }}</p>@endforelse</div></section><section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 font-bold">{{ __('Invoices & payments') }}</h2><div class="divide-y">@forelse ($invoices as $invoice)<div class="flex justify-between gap-4 px-5 py-3 text-sm"><div class="min-w-0"><p>{{ $invoice->invoice_number }}</p><span class="text-xs text-slate-500">{{ str($invoice->type)->replace('_',' ')->title() }}</span></div><span class="shrink-0 font-semibold">{{ money($invoice->amount, $invoice->currency) }}</span></div>@empty @foreach ($payments as $payment)<div class="flex justify-between gap-4 px-5 py-3 text-sm"><span class="min-w-0">{{ __('Payment #:id · :status', ['id' => $payment->id, 'status' => __(ucfirst($payment->status))]) }}</span><span class="shrink-0">{{ money($payment->amount, $payment->currency) }}</span></div>@endforeach @if ($payments->isEmpty())<p class="p-5 text-sm text-slate-500">{{ __('No invoices or payments.') }}</p>@endif @endforelse</div></section></div>
</div>
@endsection
