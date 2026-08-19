@extends('website.layouts.dashboard')

@section('title', __('Technician Profile').' | '.config('app.name'))
@section('page-title', __('Technician Profile'))
@section('page-subtitle', __('Keep your details current so customers know what you offer.'))

@section('dashboard-content')
    @php
        $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10';
        $labelClass = 'block text-sm font-semibold text-slate-800';

        $statusBadge = match ($technician->status) {
            'active' => [__('Approved'), 'bg-emerald-50 text-emerald-700'],
            'pending' => [__('Pending review'), 'bg-amber-50 text-amber-700'],
            'suspended' => [__('Suspended'), 'bg-rose-50 text-rose-700'],
            default => [__('Inactive'), 'bg-slate-100 text-slate-600'],
        };
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
                <p class="font-semibold">{{ __('Please fix the following before saving:') }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($technician->status === 'pending')
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800" role="status">
                <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="clock" aria-hidden="true"></i>
                <p>{{ __('Your application is under review. You will appear in the marketplace once an admin approves it.') }}</p>
            </div>
        @endif

        {{-- Snapshot --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">{{ __('Status') }}</p>
                <p class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge[1] }}">{{ $statusBadge[0] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Total jobs') }}</p><p class="text-xl font-bold">{{ number_format($stats['total_jobs']) }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Completed') }}</p><p class="text-xl font-bold">{{ number_format($stats['completed_jobs']) }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">{{ __('Rating') }}</p><p class="text-xl font-bold">{{ $stats['rating'] > 0 ? number_format($stats['rating'], 2) : '—' }}</p></div>
        </div>

        <form method="POST" action="{{ route('technician.profile.update') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @csrf @method('PUT')

            <div class="grid gap-5 p-6 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="category">{{ __('Service') }}</label>
                    <input id="category" class="mt-2 {{ $inputClass }} bg-slate-50 text-slate-500" value="{{ $technician->category?->name ?? '—' }}" disabled>
                    <p class="mt-1.5 text-xs text-slate-500">{{ __('Contact support to change your service category.') }}</p>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="zone_id">{{ __('Working area') }}</label>
                    <select id="zone_id" class="mt-2 {{ $inputClass }}" name="zone_id" required>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected((int) old('zone_id', $technician->zone_id) === $zone->id)>{{ $zone->name }} ({{ $zone->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="experience_years">{{ __('Years of experience') }}</label>
                    <input id="experience_years" class="mt-2 {{ $inputClass }}" name="experience_years" type="number"
                        value="{{ old('experience_years', $technician->experience_years) }}" min="0" max="60" required>
                </div>

                <div>
                    <label class="{{ $labelClass }}" for="hourly_rate">{{ __('Hourly rate') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                    <input id="hourly_rate" class="mt-2 {{ $inputClass }}" name="hourly_rate" type="number"
                        value="{{ old('hourly_rate', $technician->hourly_rate) }}" min="0">
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="skills">{{ __('Skills') }} <span class="font-normal text-slate-400">({{ __('comma separated') }})</span></label>
                    <input id="skills" class="mt-2 {{ $inputClass }}" name="skills"
                        value="{{ is_array(old('skills')) ? implode(', ', old('skills')) : old('skills', implode(', ', $technician->skills ?? [])) }}"
                        placeholder="e.g. Wiring, AC servicing">
                </div>

                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}" for="bio">{{ __('About you') }}</label>
                    <textarea id="bio" class="mt-2 {{ $inputClass }}" name="bio" rows="5" maxlength="1000">{{ old('bio', $technician->bio) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $technician->is_available))
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-semibold text-slate-800">{{ __('I am currently accepting new jobs') }}</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-6 py-4">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="h-4 w-4" data-lucide="save" aria-hidden="true"></i>{{ __('Save Changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection
