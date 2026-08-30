@extends('website.layouts.master')

@section('title', __('Book an Inspection').' | '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('properties.show', $listing) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back to property') }}
        </a>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h1 class="text-2xl font-bold text-slate-950">{{ __('Book an Inspection') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('Book a physical or virtual inspection. An independent inspector will be assigned to document the property condition.') }}</p>
            </div>

            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('inspections.store', $listing) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-slate-800">{{ __('Inspection type') }}</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="type" value="physical" @checked(old('type', 'physical') === 'physical') class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block font-semibold text-slate-900">{{ __('Physical') }}</span>
                                    <span class="text-xs text-slate-500">{{ __('On-site inspection with GPS-verified evidence') }}</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="type" value="virtual" @checked(old('type') === 'virtual') class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block font-semibold text-slate-900">{{ __('Virtual') }}</span>
                                    <span class="text-xs text-slate-500">{{ __('Live video walkthrough with the owner') }}</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="scheduled_at" class="block text-sm font-semibold text-slate-800">{{ __('Preferred date & time') }}</label>
                        <input id="scheduled_at" name="scheduled_at" type="datetime-local"
                            class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank if you are flexible — we will coordinate.') }}</p>
                    </div>

                    <p class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">
                        {{ __('An independent inspector will document the property with photos and a structured checklist. The full report must be acknowledged by you before your offer’s inspection condition is satisfied.') }}
                    </p>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                        <a href="{{ route('properties.show', $listing) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">{{ __('Cancel') }}</a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="h-4 w-4" data-lucide="calendar-check" aria-hidden="true"></i>{{ __('Request Inspection') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
