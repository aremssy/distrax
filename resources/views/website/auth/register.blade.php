@extends('website.layouts.master')

@section('title', __('Register | Rentdo'))

@section('content')
    <x-website-auth-shell
        eyebrow="{{ __('One account, every possibility') }}"
        heading="{{ __('Find a place, list a property, or hire trusted help.') }}"
        description="{{ __('Rentdo brings verified properties, local technicians, and simple rental management together in one place.') }}"
    >
        <div>
            <p class="text-sm font-semibold text-indigo-600">{{ __('Get started for free') }}</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ __('Create your account') }}</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('It only takes a minute. You can complete your profile later.') }}</p>
        </div>

        <div class="mt-7 grid grid-cols-2 rounded-xl bg-slate-100 p-1 text-sm font-semibold">
            <a class="rounded-lg px-4 py-2.5 text-center text-slate-500 transition hover:text-slate-950" href="{{ route('login') }}">{{ __('Login') }}</a>
            <span class="rounded-lg bg-white px-4 py-2.5 text-center text-slate-950 shadow-sm">{{ __('Register') }}</span>
        </div>

        @if ($errors->any())
            <div class="mt-5 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="circle-alert"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('register.store') }}" class="mt-6 grid gap-4" method="POST">
            @csrf

            <div class="grid gap-2">
                <label class="text-sm font-semibold text-slate-700" for="name">{{ __('Full name') }}</label>
                <div class="relative">
                    <i class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" data-lucide="user"></i>
                    <input class="h-12 w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="{{ __('Your full name') }}" autocomplete="name" required autofocus>
                </div>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-semibold text-slate-700" for="email">{{ __('Email address') }}</label>
                <input class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
            </div>

            <x-website.phone-input name="phone" label="{{ __('Phone') }}" :value="old('phone')" optional placeholder="1234 567890" />

            <div class="grid gap-2">
                <label class="text-sm font-semibold text-slate-700" for="password">{{ __('Password') }}</label>
                <div class="relative">
                    <i class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" data-lucide="lock-keyhole"></i>
                    <input class="h-12 w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-12 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10" id="password" name="password" type="password" placeholder="{{ __('At least 4 characters') }}" autocomplete="new-password" required data-password-input data-strength-input>
                    <button class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" type="button" data-password-toggle aria-label="{{ __('Show password') }}" aria-pressed="false">
                        <i class="h-4 w-4" data-lucide="eye"></i>
                    </button>
                </div>
                <div class="grid grid-cols-4 gap-1.5" aria-hidden="true" data-strength-meter>
                    <span class="h-1 rounded-full bg-slate-200"></span><span class="h-1 rounded-full bg-slate-200"></span><span class="h-1 rounded-full bg-slate-200"></span><span class="h-1 rounded-full bg-slate-200"></span>
                </div>
                <p class="text-xs text-slate-500" data-strength-text>{{ __('Use at least 4 characters.') }}</p>
            </div>

            <label class="flex cursor-pointer items-start gap-3 text-sm leading-5 text-slate-500" for="terms">
                <input class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" id="terms" name="terms" type="checkbox" value="1" required @checked(old('terms'))>
                <span>
                    {{ __('By continuing, you agree to our') }}
                    <a class="font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('pages.show', 'terms-of-service') }}">{{ __('Terms of Service') }}</a>
                    {{ __('and') }}
                    <a class="font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('pages.show', 'privacy-policy') }}">{{ __('Privacy Policy') }}</a>.
                </span>
            </label>

            <button class="group flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-indigo-600 hover:shadow-indigo-600/20 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 active:scale-[0.99]" type="submit">
                {{ __('Sign up') }}
                <i class="h-4 w-4 transition-transform group-hover:translate-x-0.5" data-lucide="arrow-right"></i>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            {{ __('Already have an account?') }}
            <a class="font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('login') }}">{{ __('Sign in') }}</a>
        </p>
    </x-website-auth-shell>
@endsection
