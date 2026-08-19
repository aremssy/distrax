<x-admin-guest-layout title="Reset Password">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Set new password</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Choose a strong password for your account.</p>
    </div>

    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Email address
            </label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="{{ old('email', $email) }}"
                   class="block w-full rounded-xl border px-3.5 py-2.5 text-sm
                          bg-white dark:bg-night-900 text-slate-900 dark:text-slate-100
                          border-slate-200 dark:border-night-700
                          focus:border-brand/50 focus:ring-2 focus:ring-brand/10 outline-none transition
                          @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                New password
            </label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   class="block w-full rounded-xl border px-3.5 py-2.5 text-sm
                          bg-white dark:bg-night-900 text-slate-900 dark:text-slate-100
                          border-slate-200 dark:border-night-700
                          focus:border-brand/50 focus:ring-2 focus:ring-brand/10 outline-none transition
                          @error('password') border-red-400 @enderror"
                   placeholder="Minimum 4 characters">
            @error('password')
                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Confirm password
            </label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="block w-full rounded-xl border px-3.5 py-2.5 text-sm
                          bg-white dark:bg-night-900 text-slate-900 dark:text-slate-100
                          border-slate-200 dark:border-night-700
                          focus:border-brand/50 focus:ring-2 focus:ring-brand/10 outline-none transition"
                   placeholder="Repeat new password">
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand hover:bg-indigo-700 shadow-md shadow-brand/30 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors">
            Reset password
        </button>
    </form>

</x-admin-guest-layout>
