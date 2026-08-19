<x-installer-layout title="Database">
    <h2 class="text-lg font-semibold mb-4">Database Configuration</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Enter your database credentials. The installer will create the tables automatically.</p>

    <form method="POST" action="{{ route('installer.database.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Database Host</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Port</label>
                <input type="number" name="db_port" value="{{ old('db_port', '3306') }}" required
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Database Name</label>
                <input type="text" name="db_database" value="{{ old('db_database') }}" required
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Username</label>
            <input type="text" name="db_username" value="{{ old('db_username', 'root') }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="db_password" value="{{ old('db_password') }}"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="flex flex-col gap-3 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('installer.requirements') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors sm:w-auto">
                Test & Save → <span class="text-xs opacity-70">Admin Account</span>
            </button>
        </div>
    </form>
</x-installer-layout>
