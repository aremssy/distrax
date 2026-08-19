@extends('website.layouts.dashboard')
@section('title', __('Rent Management').' | '.config('app.name'))
@section('page-title', __('Rent Management'))
@section('dashboard-content')
<div class="mx-auto max-w-6xl space-y-6">
    @unless ($hasAccess)
        <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6"><h2 class="font-bold text-indigo-950">{{ __('Advanced rent management is locked') }}</h2><p class="mt-2 text-sm text-indigo-800">{{ __('Rent reminders, receipts, income/expense tracking, agreement templates, and multi-unit tools require an eligible subscription. Choose a plan to unlock them.') }}</p><a href="{{ route('dashboard.wallet') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">{{ __('View plans') }}</a></section>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4"><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Tenancies') }}</p><p class="text-xl font-bold">{{ $tenancies->total() }}</p></div><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Income') }}</p><p class="text-xl font-bold text-emerald-600">{{ money($summary['income']) }}</p></div><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Expense') }}</p><p class="text-xl font-bold text-red-600">{{ money($summary['expense']) }}</p></div><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Net') }}</p><p class="text-xl font-bold">{{ money($summary['income'] - $summary['expense']) }}</p></div></div>

        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">{{ __('Tenancies') }}</h2><div class="flex flex-wrap gap-2"><a href="{{ route('owner.rent-management.ledger.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('Income & expenses') }}</a><a href="{{ route('owner.rent-management.agreements.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('Agreements') }}</a><a href="{{ route('owner.rent-management.tenancies.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Add tenancy') }}</a></div></div>

        <div class="space-y-3">
            @forelse ($tenancies as $tenancy)
                <article class="rounded-2xl border border-slate-200 bg-white p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><a href="{{ route('owner.rent-management.tenancies.show', $tenancy) }}" class="font-semibold text-slate-950 hover:underline">{{ $tenancy->tenantName() }}</a><p class="text-sm text-slate-500">{{ $tenancy->listing?->title }}@if ($tenancy->unit) &middot; {{ __('Unit :name', ['name' => $tenancy->unit->name]) }} @endif</p></div><div class="text-right"><p class="font-semibold">{{ money($tenancy->rent_amount) }}<span class="text-xs font-normal text-slate-500">{{ __('/mo') }}</span></p><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $tenancy->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ __(ucfirst($tenancy->status)) }}</span></div></div>@if ($tenancy->overdue_payments_count > 0)<p class="mt-3 text-xs font-semibold text-red-600">{{ __(':count overdue rent payment(s)', ['count' => $tenancy->overdue_payments_count]) }}</p>@endif</article>
            @empty
                <div class="rounded-2xl border border-slate-200 border-dashed bg-white px-6 py-12 text-center"><p class="text-sm text-slate-500">{{ __('No tenancies yet.') }}</p><a href="{{ route('owner.rent-management.tenancies.create') }}" class="mt-3 inline-block text-sm font-semibold text-indigo-600">{{ __('Add your first tenancy') }}</a></div>
            @endforelse
        </div>
        {{ $tenancies->links() }}
    @endunless
</div>
@endsection
