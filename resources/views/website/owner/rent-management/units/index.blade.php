@extends('website.layouts.dashboard')
@section('title', __('Units').' | '.config('app.name'))
@section('page-title', __('Units — :title', ['title' => $listing->title]))
@section('dashboard-content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="space-y-3">
        @forelse ($units as $unit)
            <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                <form class="flex flex-wrap items-center gap-2" action="{{ route('owner.rent-management.units.update', $unit) }}" method="POST">
                    @csrf @method('PUT')
                    <label class="sr-only" for="unit-name-{{ $unit->id }}">{{ __('Unit name') }}</label>
                    <input class="w-32 rounded-lg border border-slate-200 px-3 py-2 text-sm" id="unit-name-{{ $unit->id }}" name="name" value="{{ $unit->name }}" required>
                    <label class="sr-only" for="unit-floor-{{ $unit->id }}">{{ __('Floor') }}</label>
                    <input class="w-24 rounded-lg border border-slate-200 px-3 py-2 text-sm" id="unit-floor-{{ $unit->id }}" name="floor" value="{{ $unit->floor }}" placeholder="{{ __('Floor') }}">
                    <label class="sr-only" for="unit-rent-{{ $unit->id }}">{{ __('Rent amount') }}</label>
                    <input class="w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm" id="unit-rent-{{ $unit->id }}" name="rent_amount" type="number" min="0" value="{{ $unit->rent_amount }}" placeholder="{{ __('Rent') }}">
                    <label class="sr-only" for="unit-status-{{ $unit->id }}">{{ __('Status') }}</label>
                    <select class="rounded-lg border border-slate-200 px-3 py-2 text-sm" id="unit-status-{{ $unit->id }}" name="status">@foreach (['vacant','occupied','maintenance'] as $status)<option value="{{ $status }}" @selected($unit->status === $status)>{{ __(ucfirst($status)) }}</option>@endforeach</select>
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold" type="submit">{{ __('Save') }}</button>
                </form>
                <form action="{{ route('owner.rent-management.units.destroy', $unit) }}" method="POST" onsubmit="return confirm('{{ __('Remove this unit?') }}')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" type="submit">{{ __('Remove unit :name', ['name' => $unit->name]) }}</button></form>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 border-dashed bg-white px-6 py-12 text-center"><p class="text-sm text-slate-500">{{ __('No units yet for this property.') }}</p></div>
        @endforelse
    </div>

    <form class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-4" action="{{ route('owner.rent-management.units.store', $listing) }}" method="POST">
        @csrf
        <label class="text-xs font-semibold text-slate-600">{{ __('Unit name') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="name" placeholder="e.g. 2A" required></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Floor') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="floor"></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Rent') }}<input class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="rent_amount" type="number" min="0"></label>
        <label class="text-xs font-semibold text-slate-600">{{ __('Status') }}<select class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" name="status"><option value="vacant" selected>{{ __('Vacant') }}</option><option value="occupied">{{ __('Occupied') }}</option><option value="maintenance">{{ __('Maintenance') }}</option></select></label>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white sm:col-span-4" type="submit">{{ __('Add unit') }}</button>
    </form>
</div>
@endsection
