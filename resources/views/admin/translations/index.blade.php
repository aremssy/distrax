<x-admin-layout title="Translations">
    <x-admin-page-header title="Translation Editor"
        :breadcrumbs="[['label' => 'Settings', 'route' => 'admin.settings.index'], ['label' => 'Translations']]">
        <x-slot name="actions">
            <x-admin.export-button resource="translations" />
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.translations.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ $search }}" placeholder="Search key or value..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="language_id" onchange="this.form.submit()"
                    class="max-w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                @foreach ($languages as $lang)
                    <option value="{{ $lang->id }}" @selected($lang->id == $localeId)>{{ $lang->name }}</option>
                @endforeach
            </select>

            @if (filled($search))
                <a href="{{ route('admin.translations.index', ['language_id' => $localeId]) }}"
                   class="ml-auto flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Add new translation --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <details>
            <summary class="cursor-pointer text-sm font-semibold text-brand hover:underline dark:text-indigo-400">+ Add new translation key</summary>
            <form method="POST" action="{{ route('admin.translations.store') }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <input type="hidden" name="language_id" value="{{ $localeId }}">
                <x-admin.form.input name="group" placeholder="messages" hint="Group (e.g. messages)" />
                <x-admin.form.input name="key" placeholder="welcome" hint="Key" />
                <x-admin.form.input name="value" placeholder="Translation value" hint="Value" />
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Add</button>
                </div>
            </form>
        </details>
    </div>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Group</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Key</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Value</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($translations as $t)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $t->group }}</td>

                            <td class="px-3 py-3.5">
                                <p class="truncate font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $t->key }}</p>
                            </td>

                            <td class="px-3 py-3.5">
                                <form method="POST" action="{{ route('admin.translations.update', $t) }}" class="flex items-center gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="value" value="{{ $t->value }}"
                                           class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200">
                                    <button type="submit"
                                            class="rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-brand/30 transition-colors hover:bg-indigo-700">
                                        Save
                                    </button>
                                </form>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <form method="POST" action="{{ route('admin.translations.destroy', $t) }}"
                                              data-confirm="Delete?">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-sm text-slate-400">No translations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $translations->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
