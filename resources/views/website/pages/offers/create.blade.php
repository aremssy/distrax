@extends('website.layouts.master')

@section('title', __('Make an Offer').' | '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('properties.show', $listing) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back to property') }}
        </a>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h1 class="text-2xl font-bold text-slate-950">{{ __('Make an Offer') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('Negotiate directly with the seller. Offers are time-bound and binding if accepted.') }}</p>
            </div>

            <div class="p-6">
                <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @if ($listing->coverImage->first())
                        <img src="{{ $listing->coverImage->first()->url() }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $listing->title }}</p>
                        <p class="text-sm text-slate-500">{{ $listing->address }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-emerald-700">{{ money($listing->price, $listing->currency_code) }}</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('offers.store', $listing) }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="amount" class="block text-sm font-semibold text-slate-800">{{ __('Your offer amount') }}</label>
                            <input id="amount" name="amount" type="number" min="1" step="1" required value="{{ old('amount', $listing->price) }}"
                                class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                        </div>
                        <div>
                            <label for="currency_code" class="block text-sm font-semibold text-slate-800">{{ __('Currency') }}</label>
                            <input id="currency_code" name="currency_code" type="text" maxlength="3" value="{{ old('currency_code', $listing->currency_code ?? '') }}"
                                class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 uppercase outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10">
                        </div>
                    </div>

                    <div>
                        <label for="terms" class="block text-sm font-semibold text-slate-800">{{ __('Terms & conditions') }}</label>
                        <textarea id="terms" name="terms" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                            placeholder="{{ __('e.g. subject to financing, inspection within 14 days, target closing date…') }}">{{ old('terms') }}</textarea>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-semibold text-slate-800">{{ __('Message to the seller') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <textarea id="message" name="message" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                            placeholder="{{ __('Introduce yourself and your intentions…') }}">{{ old('message') }}</textarea>
                    </div>

                    <p class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">
                        {{ __('Your offer will be recorded in the negotiation log. The seller can accept, reject or counter it before it expires.') }}
                    </p>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                        <a href="{{ route('properties.show', $listing) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">{{ __('Cancel') }}</a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i class="h-4 w-4" data-lucide="hand-coins" aria-hidden="true"></i>{{ __('Submit Offer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
