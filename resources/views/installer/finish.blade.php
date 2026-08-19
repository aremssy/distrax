<x-installer-layout title="Installation Complete">
    <div class="text-center py-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-4">
            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        <h2 class="text-xl font-semibold mb-2">Installation Complete!</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Your application has been installed and configured successfully.</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-8">The installer has been locked to prevent re-running.</p>

        <div class="space-y-3">
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                </svg>
                Go to Dashboard
            </a>
        </div>

        @if (config('app.env') === 'local' || config('app.debug'))
            <div class="mt-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    <strong>Demo Mode:</strong> You can seed sample data using:
                    <code class="break-all font-mono bg-amber-100 dark:bg-amber-900/40 px-1 rounded sm:break-normal">php artisan db:seed --class=DemoDataSeeder</code>
                </p>
            </div>
        @endif
    </div>
</x-installer-layout>
