<x-admin-guest-layout title="Sign In" illustrated>

    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Welcome back</h1>
        <p class="mt-2 text-base text-slate-500 dark:text-slate-400">Sign in to your admin account</p>
    </div>

    @php
        $demoEmail = 'admin@rentdo.com';
        $demoPassword = 'admin@123';
    @endphp

    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-6">
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
                       class="block w-full rounded-xl border bg-slate-50 py-3.5 pl-12 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 dark:bg-night-950 dark:text-slate-100 dark:focus:bg-night-900 @error('email') border-rose-400 dark:border-rose-500 @else border-slate-200 dark:border-night-700 @enderror"
                       placeholder="{{ $demoEmail }}">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200">
                Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    @include('admin.partials.icon', ['name' => 'key', 'class' => 'size-5'])
                </span>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       data-password-input
                       class="block w-full rounded-xl border bg-slate-50 py-3.5 pl-12 pr-12 text-sm text-slate-900 outline-none transition focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 dark:bg-night-950 dark:text-slate-100 dark:focus:bg-night-900 @error('password') border-rose-400 dark:border-rose-500 @else border-slate-200 dark:border-night-700 @enderror"
                       placeholder="{{ $demoPassword }}">
                <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:text-brand dark:hover:text-slate-200">
                    @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="flex cursor-pointer items-center gap-2.5">
                <input name="remember" type="checkbox"
                       class="size-4 rounded border-slate-300 bg-white text-brand focus:ring-brand dark:border-night-600 dark:bg-night-900">
                <span class="text-sm text-slate-600 dark:text-slate-300">Remember me</span>
            </label>
            <a href="{{ route('admin.password.request') }}"
               class="text-sm font-semibold text-brand transition hover:text-indigo-700 hover:underline dark:text-indigo-400">
                Forgot password?
            </a>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-3 rounded-2xl bg-brand px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand/25 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-xl hover:shadow-brand/30 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:translate-y-0">
            Sign in
            @include('admin.partials.icon', ['name' => 'arrow-right-on-rect', 'class' => 'w-4 h-4'])
        </button>

        <div x-data="{
                 copied: false,
                 copy(email, password) {
                     this.fillField('email', email);
                     this.fillField('password', password);

                     this.copied = true;
                     setTimeout(() => this.copied = false, 2000);

                     const text = 'Email: ' + email + '\nPassword: ' + password;

                     if (navigator.clipboard && window.isSecureContext) {
                         navigator.clipboard.writeText(text).catch(() => this.copyFallback(text));
                         return;
                     }

                     this.copyFallback(text);
                 },
                 fillField(id, value) {
                     const field = document.getElementById(id);

                     if (! field) { return; }

                     field.value = value;
                     field.dispatchEvent(new Event('input', { bubbles: true }));
                     field.dispatchEvent(new Event('change', { bubbles: true }));
                 },
                 copyFallback(text) {
                     const field = document.createElement('textarea');
                     field.value = text;
                     field.setAttribute('readonly', '');
                     field.style.position = 'fixed';
                     field.style.opacity = '0';
                     document.body.appendChild(field);
                     field.select();

                     let copied = false;
                     try { copied = document.execCommand('copy'); } catch (e) { copied = false; }

                     document.body.removeChild(field);

                     return copied;
                 },
             }"
             class="mt-2 overflow-hidden rounded-2xl border border-dashed border-brand/30 bg-brand/5 dark:border-brand/25 dark:bg-brand/10">
            <div class="flex items-center gap-2 border-b border-brand/15 bg-brand/5 px-4 py-2 dark:border-brand/20">
                <span class="text-brand dark:text-indigo-400">
                    @include('admin.partials.icon', ['name' => 'key', 'class' => 'size-4'])
                </span>
                <span class="text-xs font-bold uppercase tracking-wide text-brand dark:text-indigo-300">Demo credentials</span>
            </div>
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dl class="space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    <div class="flex gap-2">
                        <dt class="w-20 shrink-0 font-bold text-slate-800 dark:text-slate-200">Email</dt>
                        <dd class="font-mono">{{ $demoEmail }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-20 shrink-0 font-bold text-slate-800 dark:text-slate-200">Password</dt>
                        <dd class="font-mono">{{ $demoPassword }}</dd>
                    </div>
                </dl>
                <button type="button"
                        @click="copy({{ Js::from($demoEmail) }}, {{ Js::from($demoPassword) }})"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-brand/30 bg-white px-3.5 py-2 text-sm font-semibold text-brand transition hover:border-brand hover:bg-brand hover:text-white focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 dark:bg-night-900 dark:text-indigo-400 dark:hover:text-white">
                    <span x-show="!copied" class="inline-flex items-center gap-1.5">
                        @include('admin.partials.icon', ['name' => 'clipboard-document-list', 'class' => 'size-4'])
                        Copy
                    </span>
                    <span x-show="copied" x-cloak class="inline-flex items-center gap-1.5">
                        @include('admin.partials.icon', ['name' => 'check', 'class' => 'size-4'])
                        Copied!
                    </span>
                </button>
            </div>
        </div>
    </form>

    <div class="mt-10 flex items-center gap-5" aria-hidden="true">
        <span class="h-px flex-1 bg-slate-200 dark:bg-night-700"></span>
        <span class="text-xs text-slate-400">Secure admin access</span>
        <span class="h-px flex-1 bg-slate-200 dark:bg-night-700"></span>
    </div>

    <p class="mt-7 text-center text-xs text-slate-500 dark:text-slate-400">
        &copy; {{ now()->year }} Rentdo. All rights reserved.
    </p>

</x-admin-guest-layout>
