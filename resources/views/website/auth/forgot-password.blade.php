@extends('website.layouts.master')

@section('title', __('Forgot Password | Rentdo'))

@section('content')
    <x-website-auth-shell
        eyebrow="{{ __('A simpler way to find home') }}"
        heading="{{ __('Locked out? Let\'s get you back in.') }}"
        description="{{ __('Enter the email linked to your account and we\'ll send you a secure link to choose a new password.') }}"
    >
        <div>
            <p class="text-sm font-semibold text-indigo-600">{{ __('Password recovery') }}</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">{{ __('Forgot your password?') }}</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ __("We'll email you a link to reset it. The link expires in 60 minutes.") }}</p>
        </div>

        @if (session('success'))
            <div class="mt-6 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">
                <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="circle-alert"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('password.email') }}" class="mt-7 grid gap-5" method="POST">
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

            <button class="group flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/15 transition hover:bg-indigo-600 hover:shadow-indigo-600/20 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 active:scale-[0.99]" type="submit">
                {{ __('Send reset link') }}
                <i class="h-4 w-4 transition-transform group-hover:translate-x-0.5" data-lucide="arrow-right"></i>
            </button>
        </form>

        <p class="mt-7 text-center text-sm text-slate-500">
            {{ __('Remembered it?') }}
            <a class="font-semibold text-indigo-600 transition hover:text-indigo-700" href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
        </p>
    </x-website-auth-shell>
@endsection
