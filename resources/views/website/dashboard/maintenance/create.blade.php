@extends('website.layouts.dashboard')
@section('title', __('New Maintenance Request').' | '.config('app.name'))
@section('page-title', __('New Maintenance Request'))
@section('dashboard-content')
    <div class="mx-auto max-w-2xl">
        <form class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6" action="{{ route('dashboard.maintenance.store') }}" method="POST">
            @csrf

            @if ($listings->isEmpty())
                <p class="text-sm text-slate-500">{{ __("You don't own or have a booking on any listing yet, so there's nothing to file a maintenance request against.") }}</p>
            @else
                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="property_listing_id">{{ __('Listing') }}</label>
                    <select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="property_listing_id" name="property_listing_id" required>
                        <option value="">{{ __('Choose a listing…') }}</option>
                        @foreach ($listings as $listing)
                            <option value="{{ $listing->id }}">{{ $listing->title }}</option>
                        @endforeach
                    </select>
                    @error('property_listing_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="title">{{ __('Title') }}</label>
                    <input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="title" name="title" required maxlength="255" placeholder="{{ __('e.g. Leaking kitchen tap') }}">
                    @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="description">{{ __('Description') }}</label>
                    <textarea class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="description" name="description" rows="4"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600" for="priority">{{ __('Priority') }}</label>
                    <select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" id="priority" name="priority">
                        @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                            <option value="{{ $priority }}" @selected($priority === 'normal')>{{ __(ucfirst($priority)) }}</option>
                        @endforeach
                    </select>
                </div>

                @error('feature')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">{{ __('Submit request') }}</button>
            @endif
        </form>
    </div>
@endsection
