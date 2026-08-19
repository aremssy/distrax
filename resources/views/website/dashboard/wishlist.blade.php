@extends('website.layouts.dashboard')

@section('title', __('Favorites & Saved Searches').' | '.config('app.name'))
@section('page-title', __('Favorites & Saved Searches'))

@section('dashboard-content')
    <div class="mx-auto max-w-6xl space-y-10">
        <section>
            <div class="mb-5 flex items-center justify-between gap-4"><h2 class="font-semibold text-slate-950">{{ __('Saved properties') }}</h2><span class="text-sm text-slate-500">{{ __(':count saved', ['count' => $favorites->total()]) }}</span></div>
            @if ($favorites->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center"><h3 class="font-semibold text-slate-900">{{ __('No saved properties yet') }}</h3><p class="mt-2 text-sm text-slate-500">{{ __('Save properties to compare and revisit them here.') }}</p><a class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('properties.index') }}">{{ __('Browse properties') }}</a></div>
            @else
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($favorites as $listing)
                        <div class="relative"><x-website.property-card :$listing /><form class="absolute right-3 top-3 z-10" action="{{ route('properties.favorite.destroy', $listing) }}" method="POST">@csrf @method('DELETE')<button class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-red-500 shadow" type="submit" aria-label="{{ __('Remove :title from favorites', ['title' => $listing->title]) }}"><i class="h-4 w-4 fill-current" data-lucide="heart"></i></button></form></div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $favorites->links() }}</div>
            @endif
        </section>

        <section>
            <div class="mb-5 flex items-center justify-between gap-4"><h2 class="font-semibold text-slate-950">{{ __('Saved searches') }}</h2><span class="text-sm text-slate-500">{{ __(':count of 20', ['count' => $savedSearches->count()]) }}</span></div>
            @if ($savedSearches->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">{{ __('Apply filters on the property search page, then save that search here.') }}</div>
            @else
                <div class="space-y-3">
                    @foreach ($savedSearches as $search)
                        <article class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center">
                            <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-950">{{ $search->name ?: __('Property search') }}</h3>@if ($search->alert_on)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('Alerts on') }}</span>@endif</div><div class="mt-2 flex flex-wrap gap-1.5">@foreach ($search->criteria as $name => $value)<span class="rounded-md bg-slate-50 px-2 py-1 text-xs text-slate-500">{{ str($name)->replace('_', ' ')->title() }}: {{ is_bool($value) ? ($value ? __('Yes') : __('No')) : $value }}</span>@endforeach</div></div>
                            <div class="flex gap-2"><a class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white" href="{{ route('properties.index', $search->criteria) }}">{{ __('Run search') }}</a><form action="{{ route('saved-searches.destroy', $search) }}" method="POST">@csrf @method('DELETE')<button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-red-600" type="submit">{{ __('Delete') }}</button></form></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
