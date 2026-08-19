@extends('website.layouts.dashboard')
@section('title', __('My Agency').' | '.config('app.name'))
@section('page-title', __('My Agency'))
@section('dashboard-content')
<div class="mx-auto max-w-4xl space-y-6">
    @if (! $agency)
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-slate-950">{{ __('Create your agency') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Set up your agency profile to add agents, bulk-import listings, and get a branded public page.') }}</p>
            <form class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2" action="{{ route('owner.agency.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600" for="agency-name">{{ __('Agency name') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-name" name="name" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="agency-zone">{{ __('Primary zone') }}</label>
                    <select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-zone" name="zone_id">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="agency-phone">{{ __('Phone') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-phone" name="phone">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="agency-email">{{ __('Contact email') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-email" name="email" type="email">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="agency-website">{{ __('Website') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-website" name="website" type="url" placeholder="https://">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600" for="agency-address">{{ __('Address') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-address" name="address">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600" for="agency-logo">{{ __('Logo') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-logo" name="logo" type="file" accept="image/*">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600" for="agency-description">{{ __('Description') }}</label>
                    <textarea class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="agency-description" name="description" rows="3"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Create agency') }}</button>
                </div>
            </form>
        </div>
    @else
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">
                        {{ $agency->name }}
                        @if ($agency->is_verified)<span class="ml-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ __('Verified') }}</span>@endif
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $agency->zone?->name }} @if ($agency->phone) &middot; {{ $agency->phone }} @endif</p>
                </div>
                <a href="{{ route('agencies.show', $agency->slug) }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">{{ __('View public page') }}</a>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-label="{{ __('Bulk import listings') }}">
            <h2 class="font-bold text-slate-950">{{ __('Bulk import listings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Upload a CSV with columns') }} <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">title, type, zone_slug, price, description, bedrooms, bathrooms, address</code>.
                {{ __('Imported listings are added as pending, same as any owner submission — an admin reviews them before they go live.') }}
            </p>
            <form class="mt-4 flex flex-wrap items-end gap-2" action="{{ route('owner.agency.listings.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="min-w-48 flex-1">
                    <label class="block text-xs font-semibold text-slate-600" for="csv_file">{{ __('CSV file') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="csv_file" name="csv_file" type="file" accept=".csv,.txt" required>
                    @error('csv_file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Import listings') }}</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ __('Agency agents') }}">
            <h2 class="p-5 font-bold">{{ __('Agents (:count)', ['count' => $agency->agents->count()]) }}</h2>
            <div class="divide-y">
                @forelse ($agency->agents as $agent)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div>
                            <p class="font-semibold text-slate-950">
                                {{ $agent->user?->name ?? __('Unknown') }}
                                @unless ($agent->is_active)<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('Inactive') }}</span>@endunless
                            </p>
                            <p class="text-xs text-slate-500">{{ $agent->user?->email }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form class="flex flex-wrap items-center gap-2" action="{{ route('owner.agency.agents.update', $agent) }}" method="POST">
                                @csrf @method('PUT')
                                <label class="sr-only" for="agent-title-{{ $agent->id }}">{{ __('Title for :name', ['name' => $agent->user?->name]) }}</label>
                                <input class="w-32 rounded-lg border border-slate-200 px-3 py-1.5 text-xs" id="agent-title-{{ $agent->id }}" name="title" value="{{ $agent->title }}" placeholder="{{ __('Title') }}">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($agent->is_active)> {{ __('Active') }}
                                </label>
                                <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold" type="submit">{{ __('Save') }}</button>
                            </form>
                            <form action="{{ route('owner.agency.agents.destroy', $agent) }}" method="POST" onsubmit="return confirm('{{ __('Remove this agent from your team?') }}')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600" type="submit">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500">{{ __('No agents on your team yet. Add one below using their registered account email.') }}</p>
                @endforelse
            </div>

            <form class="flex flex-wrap items-end gap-2 border-t p-5" action="{{ route('owner.agency.agents.store') }}" method="POST">
                @csrf
                <div class="min-w-48 flex-1">
                    <label class="block text-xs font-semibold text-slate-600" for="user_email">{{ __('Add agent by account email') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="user_email" name="user_email" type="email" placeholder="agent@example.com" required>
                </div>
                <input class="w-40 rounded-lg border border-slate-200 px-3 py-2 text-sm" name="title" placeholder="{{ __('Title (optional)') }}" aria-label="{{ __('Agent title') }}">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Add agent') }}</button>
            </form>
        </section>
    @endif
</div>
@endsection
