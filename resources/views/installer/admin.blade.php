<x-installer-layout title="Admin Account">
    <h2 class="text-lg font-semibold mb-4">Create Admin Account</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Create the super administrator account for the application.</p>

    <form method="POST" action="{{ route('installer.admin.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required minlength="4"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" required minlength="4"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="flex flex-col gap-3 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('installer.database') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors sm:w-auto">
                Continue → <span class="text-xs opacity-70">Settings</span>
            </button>
        </div>
    </form>
</x-installer-layout>
