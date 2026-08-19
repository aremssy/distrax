<x-installer-layout title="Requirements">
    <h2 class="text-lg font-semibold mb-4">Server Requirements</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Checking if your server meets the minimum requirements.</p>

    <div class="space-y-4">
        {{-- PHP Version --}}
        <div class="flex items-center justify-between py-2 px-4 rounded-lg {{ $phpOk ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
            <div class="min-w-0">
                <span class="text-sm font-medium">PHP {{ config('installer.php_min_version') }}+</span>
                <p class="text-xs text-slate-500">Required: {{ config('installer.php_min_version') }}+ · Current: {{ $phpVersion }}</p>
            </div>
            @if ($phpOk)
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            @else
                <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            @endif
        </div>

        {{-- Extensions --}}
        @foreach ($extensions as $ext => $loaded)
            <div class="flex items-center justify-between py-2 px-4 rounded-lg {{ $loaded ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                <span class="text-sm">{{ $ext }}</span>
                @if ($loaded)
                    <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                @else
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                @endif
            </div>
        @endforeach

        {{-- Folder permissions --}}
        <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
            <h3 class="text-sm font-medium mb-3">Folder Permissions</h3>
            @foreach ($folders as $folder => $writable)
                <div class="flex items-center justify-between py-2 px-4 rounded-lg {{ $writable ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                    <span class="min-w-0 break-all text-sm font-mono sm:break-normal">{{ $folder }}</span>
                    @if ($writable)
                        <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    @else
                        <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        @if ($passes)
            <a href="{{ route('installer.database') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                Continue → <span class="text-xs opacity-70">Database Setup</span>
            </a>
        @else
            <p class="text-sm text-red-600 dark:text-red-400">Please fix the issues above before continuing.</p>
        @endif
    </div>
</x-installer-layout>
