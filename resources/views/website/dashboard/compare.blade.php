@extends('website.layouts.dashboard')
@section('title', __('Compare Properties').' | '.config('app.name'))
@section('page-title', __('Compare Properties'))
@section('dashboard-content')
    <div class="space-y-6">
        @if ($listings->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center"><h3 class="font-semibold text-slate-900">{{ __('No properties to compare') }}</h3><p class="mt-2 text-sm text-slate-500">{{ __('Add up to 5 properties from a listing page to compare them side by side.') }}</p><a class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('properties.index') }}">{{ __('Browse properties') }}</a></div>
        @else
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">{{ __(':count of 5', ['count' => $listings->count()]) }}</span>
                <form action="{{ route('compare.clear') }}" method="POST">@csrf @method('DELETE')<button class="text-sm font-semibold text-red-600" type="submit">{{ __('Clear all') }}</button></form>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="w-40 px-4 py-4 text-left text-slate-500"></th>
                            @foreach ($listings as $listing)
                                <th class="min-w-[200px] px-4 py-4 text-left align-top">
                                    <a class="font-semibold text-slate-950 hover:text-indigo-600" href="{{ route('properties.show', $listing) }}">{{ $listing->title }}</a>
                                    <form class="mt-2" action="{{ route('properties.compare.destroy', $listing) }}" method="POST">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600" type="submit">{{ __('Remove') }}</button></form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ([
                            __('Price') => fn ($l) => moneyFrom($l->price, $l->currency_code),
                            __('Type') => fn ($l) => ucfirst($l->type),
                            __('Zone') => fn ($l) => $l->zone?->name ?? '—',
                            __('Bedrooms') => fn ($l) => $l->bedrooms ?? '—',
                            __('Bathrooms') => fn ($l) => $l->bathrooms ?? '—',
                            __('Area (sqft)') => fn ($l) => $l->area_sqft ? number_format($l->area_sqft) : '—',
                            __('Floor') => fn ($l) => $l->floor ?? '—',
                            __('Furnished') => fn ($l) => $l->furnished ? __('Yes') : __('No'),
                            __('Parking') => fn ($l) => $l->parking ? __('Yes') : __('No'),
                            __('Verified') => fn ($l) => $l->is_verified ? __('Yes') : __('No'),
                        ] as $label => $accessor)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-500">{{ $label }}</td>
                                @foreach ($listings as $listing)
                                    <td class="px-4 py-3 text-slate-800">{{ $accessor($listing) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
