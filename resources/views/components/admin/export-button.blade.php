@props([
    'resource',           // export profile key, e.g. "users"
    'selectable' => false, // page has row checkboxes carrying data-export-id
    'label' => 'Export',
])

{{--
    Reusable export control. Drop <x-admin.export-button resource="users" /> into a page
    header's actions slot. The dialog reads the current URL's filters so the export always
    matches what the listing shows, offers CSV / Excel / PDF / Print, scope and column
    selection, and reports progress for queued (large) exports.
--}}
<div
    x-data="exportDialog({
        resource: '{{ $resource }}',
        optionsUrl: '{{ route('admin.exports.options', ['resource' => $resource]) }}',
        storeUrl: '{{ route('admin.exports.store', ['resource' => $resource]) }}',
        printUrl: '{{ route('admin.exports.print', ['resource' => $resource]) }}',
        selectable: {{ $selectable ? 'true' : 'false' }},
    })"
    class="relative"
>
    <button
        type="button"
        @click="openDialog()"
        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800"
    >
        @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4 text-slate-400'])
        {{ $label }}
    </button>

    {{-- Dialog --}}
    <div x-cloak x-show="open" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" x-transition.opacity>
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-night-700 dark:bg-night-900"
                @click.away="close()"
                x-transition
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-night-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Export <span x-text="title"></span>
                    </h3>
                    <button type="button" @click="close()" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-5 h-5'])
                    </button>
                </div>

                {{-- Body --}}
                <div class="space-y-5 px-6 py-5">
                    {{-- Loading options --}}
                    <div x-show="loading" class="py-8 text-center text-sm text-slate-400">Loading export options…</div>

                    <template x-if="!loading && phase === 'idle'">
                        <div class="space-y-5">
                            {{-- Format --}}
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-slate-400">Format</p>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <template x-for="fmt in formatOptions" :key="fmt.value">
                                        <label
                                            class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-sm font-medium transition-colors"
                                            :class="format === fmt.value
                                                ? 'border-brand bg-brand/10 text-brand'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:text-slate-300 dark:hover:bg-night-800'"
                                        >
                                            <input type="radio" class="sr-only" :value="fmt.value" x-model="format">
                                            <span x-text="fmt.label"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            {{-- Scope --}}
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-slate-400">Records</p>
                                <div class="space-y-2">
                                    <template x-for="opt in scopeOptions" :key="opt.value">
                                        <label
                                            class="flex cursor-pointer items-center justify-between rounded-xl border px-3.5 py-2.5 text-sm transition-colors"
                                            :class="scope === opt.value
                                                ? 'border-brand bg-brand/10 text-slate-900 dark:text-white'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:text-slate-300 dark:hover:bg-night-800'"
                                        >
                                            <span class="flex items-center gap-2.5">
                                                <input type="radio" class="text-brand focus:ring-brand" :value="opt.value" x-model="scope">
                                                <span x-text="opt.label"></span>
                                            </span>
                                            <span class="text-xs text-slate-400" x-text="opt.count"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            {{-- Columns --}}
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Columns</p>
                                    <button type="button" @click="toggleAllColumns()" class="text-xs font-medium text-brand hover:underline" x-text="allColumnsSelected ? 'Clear all' : 'Select all'"></button>
                                </div>
                                <div class="grid max-h-40 grid-cols-2 gap-x-4 gap-y-1.5 overflow-y-auto rounded-xl border border-slate-100 p-3 dark:border-night-800">
                                    <template x-for="(colLabel, colKey) in columns" :key="colKey">
                                        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <input type="checkbox" :value="colKey" x-model="selectedColumns" class="rounded border-slate-300 text-brand focus:ring-brand">
                                            <span x-text="colLabel"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Progress / result --}}
                    <template x-if="phase === 'working'">
                        <div class="space-y-3 py-4">
                            <p class="text-sm text-slate-600 dark:text-slate-300" x-text="progressLabel"></p>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-night-800">
                                <div class="h-full rounded-full bg-brand transition-all duration-300" :style="`width: ${progress}%`"></div>
                            </div>
                        </div>
                    </template>

                    <template x-if="phase === 'done'">
                        <div class="space-y-3 py-4 text-center">
                            <p class="text-sm font-medium text-emerald-600">Your export is ready.</p>
                            <a :href="downloadUrl" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 hover:bg-indigo-700">
                                @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4'])
                                Download file
                            </a>
                        </div>
                    </template>

                    <template x-if="phase === 'error'">
                        <div class="py-4 text-center text-sm text-rose-600" x-text="error"></div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-night-800">
                    <button type="button" @click="close()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:text-slate-300 dark:hover:bg-night-800">
                        <span x-text="phase === 'done' ? 'Close' : 'Cancel'"></span>
                    </button>
                    <button
                        type="button"
                        x-show="phase === 'idle'"
                        @click="submit()"
                        :disabled="selectedColumns.length === 0"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4'])
                        Export now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
