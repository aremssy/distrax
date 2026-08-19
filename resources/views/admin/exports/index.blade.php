@php
    $statusColors = [
        'completed' => 'green',
        'failed' => 'red',
        'processing' => 'blue',
        'queued' => 'yellow',
    ];
@endphp

<x-admin-layout title="Export History">
    <x-admin-page-header title="Export History" description="Files you have exported. Downloads remain available until they expire.">
        <x-slot name="actions">
            <a href="{{ url()->previous() }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'w-4 h-4 text-slate-400'])
                Back
            </a>
        </x-slot>
    </x-admin-page-header>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-800 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/70 dark:bg-night-950/40">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                        <th class="px-5 py-3">File</th>
                        <th class="px-5 py-3">Resource</th>
                        <th class="px-5 py-3">Format</th>
                        <th class="px-5 py-3">Scope</th>
                        <th class="px-5 py-3">Rows</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($exports as $export)
                        <tr class="text-slate-600 dark:text-slate-300">
                            <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                {{ $export->file_name ?? '—' }}
                                @if ($export->download_count)
                                    <span class="ml-1 text-xs text-slate-400">({{ $export->download_count }} downloads)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">{{ \Illuminate\Support\Str::headline($export->resource) }}</td>
                            <td class="px-5 py-3 uppercase">{{ $export->format }}</td>
                            <td class="px-5 py-3 capitalize">{{ $export->scope }}</td>
                            <td class="px-5 py-3">{{ $export->total_rows !== null ? number_format($export->total_rows) : '—' }}</td>
                            <td class="px-5 py-3">
                                <x-admin-badge :color="$statusColors[$export->status] ?? 'gray'">{{ ucfirst($export->status) }}</x-admin-badge>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $export->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($export->isCompleted() && ! $export->hasExpired())
                                        <a href="{{ route('admin.exports.download', $export) }}" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-brand dark:hover:bg-night-800" title="Download">
                                            @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4'])
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.exports.destroy', $export) }}" data-confirm="Delete this export file?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-night-800" title="Delete">
                                            @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">You haven't exported anything yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $exports->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
