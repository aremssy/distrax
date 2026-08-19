<x-admin-guest-layout title="Forgot Password" illustrated>

    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Reset your password</h1>
        <p class="mt-2 max-w-md text-base leading-7 text-slate-500 dark:text-slate-400">
            Enter your admin email address and we'll send you a secure password reset link.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-6">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200">
                Email address
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    @include('admin.partials.icon', ['name' => 'envelope', 'class' => 'size-5'])
                </span>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="{{ old('email') }}"
                       class="block w-full rounded-xl border bg-white/55 py-3.5 pl-12 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand focus:bg-white/80 focus:ring-4 focus:ring-brand/10 dark:bg-night-950/55 dark:text-slate-100 dark:focus:bg-night-900/80 @error('email') border-rose-400 dark:border-rose-500 @else border-slate-200/80 dark:border-night-700 @enderror"
                       placeholder="admin@rentdo.com">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-3 rounded-xl bg-brand px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand/25 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-xl hover:shadow-brand/30 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:translate-y-0">
            Send reset link
            @include('admin.partials.icon', ['name' => 'arrow-right', 'class' => 'size-4'])
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-2 font-semibold text-brand transition hover:text-indigo-700 hover:underline dark:text-indigo-400">
            @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'size-4'])
            Back to sign in
        </a>
    </p>

    <div class="mt-10 flex items-center gap-5" aria-hidden="true">
        <span class="h-px flex-1 bg-slate-200/80 dark:bg-night-700"></span>
        <span class="text-xs text-slate-400">Secure password recovery</span>
        <span class="h-px flex-1 bg-slate-200/80 dark:bg-night-700"></span>
    </div>

    <p class="mt-7 text-center text-xs text-slate-500 dark:text-slate-400">
        &copy; {{ now()->year }} Rentdo. All rights reserved.
    </p>

</x-admin-guest-layout>
