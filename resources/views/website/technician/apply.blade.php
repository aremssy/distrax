@extends('website.layouts.dashboard')

@section('title', __('Become a Technician').' | '.config('app.name'))
@section('page-title', __('Become a Technician'))
@section('page-subtitle', __('Tell us what you do and where you work. We review every application before your profile goes live.'))

@section('dashboard-content')
    @php
        $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10';
        $labelClass = 'block text-sm font-semibold text-slate-800';
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
                <p class="font-semibold">{{ __('Please fix the following before submitting:') }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('technician.apply.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @csrf

            <div class="grid gap-5 p-6 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="technician_category_id">{{ __('What do you do?') }}</label>
                    <select id="technician_category_id" class="mt-2 {{ $inputClass }}" name="technician_category_id" required>
                        <option value="">{{ __('Select a service') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('technician_category_id') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="zone_id">{{ __('Where do you work?') }}</label>
                    <select id="zone_id" class="mt-2 {{ $inputClass }}" name="zone_id" required>
                        <option value="">{{ __('Select an area') }}</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected((int) old('zone_id') === $zone->id)>{{ $zone->name }} ({{ $zone->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="experience_years">{{ __('Years of experience') }}</label>
                    <input id="experience_years" class="mt-2 {{ $inputClass }}" name="experience_years" type="number"
                        value="{{ old('experience_years', 0) }}" min="0" max="60" required>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="hourly_rate">{{ __('Hourly rate') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                    <input id="hourly_rate" class="mt-2 {{ $inputClass }}" name="hourly_rate" type="number"
                        value="{{ old('hourly_rate') }}" min="0" placeholder="e.g. 500">
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="skills">{{ __('Skills') }} <span class="font-normal text-slate-400">({{ __('comma separated') }})</span></label>
                    <input id="skills" class="mt-2 {{ $inputClass }}" name="skills"
                        value="{{ is_array(old('skills')) ? implode(', ', old('skills')) : old('skills') }}"
                        placeholder="{{ __('e.g. Wiring, AC servicing, Panel installation') }}">
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="bio">{{ __('About you') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                    <textarea id="bio" class="mt-2 {{ $inputClass }}" name="bio" rows="5" maxlength="1000"
                        placeholder="{{ __('Tell customers about your experience and the kind of work you take on.') }}">{{ old('bio') }}</textarea>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <p class="text-xs text-slate-500">{{ __('Your profile stays hidden until an admin approves it.') }}</p>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>{{ __('Submit Application') }}
                </button>
            </div>
        </form>
    </div>
@endsection
