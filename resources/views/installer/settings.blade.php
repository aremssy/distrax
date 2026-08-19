<x-installer-layout title="Settings">
    <h2 class="text-lg font-semibold mb-4">Basic Settings</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Configure the initial application settings.</p>

    <form method="POST" action="{{ route('installer.settings.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Site Name</label>
            <input type="text" name="site_name" value="{{ old('site_name', config('app.name')) }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Default Language</label>
            <select name="default_language" required
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @foreach ($languages as $lang)
                    <option value="{{ $lang->id }}" @selected(old('default_language') == $lang->id || $lang->is_default)>{{ $lang->name }} ({{ $lang->code }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Default Currency</label>
            <select name="default_currency" required
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" @selected(old('default_currency') == $currency->id || $currency->is_default)>{{ $currency->name }} ({{ $currency->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-3 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('installer.admin') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors sm:w-auto">
                Continue → <span class="text-xs opacity-70">License</span>
            </button>
        </div>
    </form>
</x-installer-layout>
