<x-installer-layout title="License Activation">
    <h2 class="text-lg font-semibold mb-4">License Activation</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Enter your Envato purchase code to activate the license.</p>

    <form method="POST" action="{{ route('installer.license.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Purchase Code</label>
            <input type="text" name="purchase_code" value="{{ old('purchase_code') }}" required
                   placeholder="e.g. a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <p class="mt-1 text-xs text-slate-400">Find your purchase code in your Envato downloads section.</p>
        </div>

        <div class="flex flex-col gap-3 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('installer.settings') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors sm:w-auto">
                Activate → <span class="text-xs opacity-70">Finish</span>
            </button>
        </div>
    </form>
</x-installer-layout>
