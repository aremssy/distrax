@extends('website.layouts.dashboard')
@section('title', __('Settings').' | '.config('app.name'))
@section('page-title', __('Settings'))

@section('dashboard-content')
    @php
        $cardClass = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6';
        $passwordFields = [
            ['current_password', __('Current password'), 'current-password', false],
            ['password', __('New password'), 'new-password', true],
            ['password_confirmation', __('Confirm password'), 'new-password', true],
        ];
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Export your data --}}
        <section class="{{ $cardClass }}" x-data="{ format: 'json' }">
            <h2 class="text-base font-bold text-slate-950">{{ __('Export your data') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Download a copy of your account data — profile, listings, wallet, saved searches and notification preferences. Choose a format.') }}</p>

            <form action="{{ route('account.export') }}" method="POST" class="mt-4 flex flex-wrap items-center gap-3">
                @csrf
                <input type="hidden" name="format" :value="format">

                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                    @foreach (['json' => 'JSON', 'csv' => 'CSV', 'pdf' => 'PDF'] as $value => $label)
                        <button type="button" @click="format = '{{ $value }}'"
                            class="rounded-lg px-3.5 py-1.5 text-sm font-semibold transition"
                            :class="format === '{{ $value }}' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="h-4 w-4" data-lucide="download" aria-hidden="true"></i>{{ __('Export my data') }}
                </button>
            </form>
        </section>

        {{-- Notification preferences --}}
        <form class="{{ $cardClass }}" action="{{ route('account.preferences') }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-5">
                <h2 class="text-base font-bold text-slate-950">{{ __('Notification preferences') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __("Choose how you'd like to hear from us for each type of update.") }}</p>
            </div>

            <div class="-mx-5 overflow-x-auto px-5 sm:mx-0 sm:px-0">
                <table class="w-full min-w-[500px] border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                            <th class="pb-3 pr-4 text-left font-semibold">{{ __('Event') }}</th>
                            @foreach (['in_app', 'push', 'email', 'sms'] as $channel)
                                <th class="px-2 pb-3 text-center font-semibold">{{ str($channel)->replace('_', ' ')->title() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['new_message' => __('Messages'), 'booking_update' => __('Bookings'), 'saved_search' => __('Saved searches'), 'promotions' => __('Promotions')] as $event => $label)
                            <tr class="group">
                                <td class="border-t border-slate-100 py-3.5 pr-4 font-medium text-slate-700 transition group-hover:text-slate-950">{{ $label }}</td>
                                @foreach (['in_app', 'push', 'email', 'sms'] as $channel)
                                    <td class="border-t border-slate-100 px-2 py-3.5 text-center">
                                        <input type="checkbox"
                                            class="h-4.5 w-4.5 cursor-pointer rounded border-slate-300 accent-indigo-600 transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                            name="preferences[{{ $event }}][{{ $channel }}]" value="1"
                                            @checked((bool) ($preferences->get($event)?->get($channel) ?? false))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700">
                <i class="h-4 w-4" data-lucide="check" aria-hidden="true"></i>{{ __('Save preferences') }}
            </button>
        </form>

        {{-- Password & security --}}
        <form class="{{ $cardClass }} space-y-4" action="{{ route('account.password') }}" method="POST">
            @csrf @method('PUT')
            <div>
                <h2 class="text-base font-bold text-slate-950">{{ __('Password & security') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __("Use a strong password you don't reuse anywhere else.") }}</p>
            </div>

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 ps-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($passwordFields as [$name, $placeholder, $autocomplete, $half])
                    <div x-data="{ show: false }" class="relative {{ $half ? '' : 'sm:col-span-2' }}">
                        <input :type="show ? 'text' : 'password'" name="{{ $name }}" required autocomplete="{{ $autocomplete }}"
                            placeholder="{{ $placeholder }}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-11 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <button type="button" @click="show = ! show" tabindex="-1"
                            :aria-label="show ? '{{ __('Hide :field', ['field' => $placeholder]) }}' : '{{ __('Show :field', ['field' => $placeholder]) }}'"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition hover:text-slate-600">
                            <span x-show="! show"><i class="h-4 w-4" data-lucide="eye" aria-hidden="true"></i></span>
                            <span x-show="show" x-cloak><i class="h-4 w-4" data-lucide="eye-off" aria-hidden="true"></i></span>
                        </button>
                    </div>
                @endforeach
            </div>

            <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700">
                <i class="h-4 w-4" data-lucide="lock" aria-hidden="true"></i>{{ __('Update password') }}
            </button>
        </form>

        {{-- Blocked users --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-base font-bold text-slate-950">{{ __('Blocked users') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __("People you've blocked can't message you or see your contact details.") }}</p>
            <div class="mt-4 space-y-2">
                @forelse ($blocks as $block)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                        <span class="text-sm font-medium text-slate-700">{{ $block->blocked->name }}</span>
                        <form action="{{ route('account.blocks.destroy', $block) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="text-sm font-semibold text-red-600 transition hover:text-red-700">{{ __('Unblock') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">{{ __('You have not blocked anyone.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- Privacy & data --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-base font-bold text-slate-950">{{ __('Privacy & data') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Request that your account be permanently removed.') }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <form action="{{ route('account.deletion') }}" method="POST">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                        <i class="h-4 w-4" data-lucide="trash-2" aria-hidden="true"></i>{{ __('Request account deletion') }}
                    </button>
                </form>
            </div>
        </section>

    </div>
@endsection
