@extends('website.layouts.master')

@section('title', __('Login | Rentdo'))

@section('content')
    <x-website-auth-shell
        eyebrow="{{ __('A simpler way to find home') }}"
        heading="{{ __('Your next move starts right where you left off.') }}"
        description="{{ __('Save properties, connect with verified owners, and manage every booking from one calm, secure workspace.') }}"
    >
        <div>
            <p class="text-sm font-semibold text-indigo-600">{{ __('Welcome back') }}</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ __('Sign in to your account') }}</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('Enter your details to continue your property journey.') }}</p>
        </div>

        <div class="mt-8 grid grid-cols-2 rounded-xl bg-slate-100 p-1 text-sm font-semibold">
            <span class="rounded-lg bg-white px-4 py-2.5 text-center text-slate-950 shadow-sm">{{ __('Login') }}</span>
            <a class="rounded-lg px-4 py-2.5 text-center text-slate-500 transition hover:text-slate-950" href="{{ route('register') }}">{{ __('Register') }}</a>
        </div>

        @if ($errors->any())
            <div class="mt-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="circle-alert"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.store') }}" class="mt-7 grid gap-5" method="POST">
            @csrf

            <div class="grid gap-2">
                <label class="text-sm font-semibold text-slate-700" for="email">{{ __('Email address') }}</label>
                <div class="relative">
                    <i class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" data-lucide="mail"></i>
                    <input
                        class="h-13 w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                        id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com"
                        autocomplete="email" required autofocus
                    >
                </div>
            </div>

            <div class="grid gap-2">
                <label class="text-sm font-semibold text-slate-700" for="password">{{ __('Password') }}</label>
                <div class="relative">
                    <i class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" data-lucide="lock-keyhole"></i>
                    <input
                        class="h-13 w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-12 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                        id="password" name="password" type="password" placeholder="{{ __('Enter your password') }}"
                        autocomplete="current-password" required data-password-input
                    >
                    <button class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" type="button" data-password-toggle aria-label="{{ __('Show password') }}" aria-pressed="false">
                        <i class="h-4 w-4" data-lucide="eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="flex w-fit cursor-pointer items-center gap-3 text-sm text-slate-600" for="remember">
                    <input class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                    {{ __('Keep me signed in') }}
                </label>
                <a class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            </div>

            <button class="group flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-indigo-600 hover:shadow-indigo-600/20 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 active:scale-[0.99]" type="submit">
                {{ __('Sign in') }}
                <i class="h-4 w-4 transition-transform group-hover:translate-x-0.5" data-lucide="arrow-right"></i>
            </button>
        </form>

        <p class="mt-7 text-center text-sm text-slate-500">
            {{ __('New to Rentdo?') }}
            <a class="font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('register') }}">{{ __('Create a free account') }}</a>
        </p>
    </x-website-auth-shell>
@endsection
