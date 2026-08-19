@extends('website.layouts.dashboard')
@section('title', __('Income & Expenses').' | '.config('app.name'))
@section('page-title', __('Income & Expenses'))
@section('dashboard-content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3"><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Income') }}</p><p class="text-xl font-bold text-emerald-600">{{ money($income) }}</p></div><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Expense') }}</p><p class="text-xl font-bold text-red-600">{{ money($expense) }}</p></div><div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Net') }}</p><p class="text-xl font-bold">{{ money($income - $expense) }}</p></div></div>

    <form class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-3" action="{{ route('owner.rent-management.ledger.store') }}" method="POST">
        @csrf
        <label class="text-xs font-semibold text-slate-600">{{ __('Type') }}<select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="type" required><option value="income">{{ __('Income') }}</option><option value="expense">{{ __('Expense') }}</option></select></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Category') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="category" placeholder="{{ __('e.g. rent, repairs') }}" required></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Amount') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="amount" type="number" min="1" required></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Date') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="occurred_on" type="date" required></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Listing') }}<select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="property_listing_id"><option value="">{{ __('No specific listing') }}</option>@foreach ($listings as $listing)<option value="{{ $listing->id }}">{{ $listing->title }}</option>@endforeach</select></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Description') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="description" placeholder="{{ __('Optional') }}"></label>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white sm:col-span-3" type="submit">{{ __('Record transaction') }}</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 font-bold">{{ __('Transactions') }}</h2><div class="divide-y">@forelse ($transactions as $transaction)<div class="flex justify-between gap-4 px-5 py-3 text-sm"><div class="min-w-0"><p>{{ $transaction->category }}@if ($transaction->description) &middot; {{ $transaction->description }} @endif</p><time class="text-xs text-slate-500">{{ $transaction->occurred_on->format('M j, Y') }}</time></div><span class="shrink-0 font-semibold {{ $transaction->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">{{ $transaction->type === 'income' ? '+' : '-' }}{{ money($transaction->amount) }}</span></div>@empty<p class="p-5 text-sm text-slate-500">{{ __('No transactions recorded yet.') }}</p>@endforelse</div></section>
    {{ $transactions->links() }}
</div>
@endsection
