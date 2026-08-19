@extends('website.layouts.dashboard')
@section('title', __('Leads & Analytics').' | '.config('app.name'))
@section('page-title', __('Leads & Analytics'))
@section('dashboard-content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Views') }}</p><p class="text-xl font-bold">{{ number_format($summary['total_views']) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Leads') }}</p><p class="text-xl font-bold">{{ number_format($summary['total_leads']) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Conversion') }}</p><p class="text-xl font-bold">{{ $summary['conversion_rate'] }}%</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Visit requests') }}</p><p class="text-xl font-bold">{{ number_format($summary['leads_by_type']['visits']) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Contact reveals') }}</p><p class="text-xl font-bold">{{ number_format($summary['leads_by_type']['contact_reveals']) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Inquiries') }}</p><p class="text-xl font-bold">{{ number_format($summary['leads_by_type']['messages']) }}</p></div>
    </div>

    <form class="flex flex-wrap items-center gap-2" action="{{ route('owner.leads.index') }}" method="GET">
        <label class="text-sm font-semibold text-slate-600" for="property_listing_id">{{ __('Filter by listing') }}</label>
        <select class="w-full min-w-0 rounded-lg border border-slate-200 px-3 py-2 text-sm sm:w-auto" name="property_listing_id" id="property_listing_id" onchange="this.form.submit()">
            <option value="">{{ __('All listings') }}</option>
            @foreach ($listings as $listing)
                <option value="{{ $listing->id }}" @selected($selectedListingId === $listing->id)>{{ $listing->title }}</option>
            @endforeach
        </select>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Lead activity feed') }}">
        <h2 class="p-5 font-bold">{{ __('Lead activity') }}</h2>
        <div class="divide-y">
            @forelse ($leads as $lead)
                @php
                    $badge = match ($lead['type']) {
                        'visit' => [__('Visit request'), 'bg-indigo-50 text-indigo-700'],
                        'contact_reveal' => [__('Contact reveal'), 'bg-amber-50 text-amber-700'],
                        default => [__('Inquiry'), 'bg-emerald-50 text-emerald-700'],
                    };
                @endphp
                <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3 text-sm">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                            <span class="font-semibold text-slate-950">{{ $lead['user_name'] ?? __('Someone') }}</span>
                        </div>
                        <p class="mt-1 text-slate-500">{{ $lead['listing_title'] }}</p>
                        @if ($lead['note'])<p class="mt-1 text-xs text-slate-500">{{ $lead['note'] }}</p>@endif
                    </div>
                    <time class="text-xs text-slate-500" datetime="{{ $lead['occurred_at']?->toIso8601String() }}">{{ $lead['occurred_at']?->diffForHumans() }}</time>
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500">{{ __('No leads yet. Views, visit requests, contact reveals, and inquiries on your listings will appear here.') }}</p>
            @endforelse
        </div>
    </section>
    {{ $leads->links() }}

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Per-listing analytics') }}">
        <h2 class="p-5 font-bold">{{ __('Per-listing performance') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th scope="col" class="px-5 py-3">{{ __('Listing') }}</th><th scope="col" class="px-5 py-3">{{ __('Views') }}</th><th scope="col" class="px-5 py-3">{{ __('Visits') }}</th><th scope="col" class="px-5 py-3">{{ __('Reveals') }}</th><th scope="col" class="px-5 py-3">{{ __('Inquiries') }}</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($summary['per_listing'] as $row)
                        <tr><td class="px-5 py-3 font-medium text-slate-950">{{ $row['title'] }}</td><td class="px-5 py-3">{{ number_format($row['views']) }}</td><td class="px-5 py-3">{{ $row['visits'] }}</td><td class="px-5 py-3">{{ $row['contact_reveals'] }}</td><td class="px-5 py-3">{{ $row['messages'] }}</td></tr>
                    @empty
                        <tr><td class="px-5 py-6 text-slate-500" colspan="5">{{ __('You have no listings yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
