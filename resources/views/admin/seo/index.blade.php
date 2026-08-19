<x-admin-layout title="SEO Meta">
    <x-admin-page-header title="SEO Meta"
        :breadcrumbs="[['label' => 'SEO']]"
        description="Manage SEO metadata across the platform.">
        <x-slot name="actions">
            <x-admin.export-button resource="seo" />
            @can('seo.edit')
                <button onclick="openModal('create-seo-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.seo.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="type" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Types</option>
                @foreach (array_keys(\App\Models\SeoMeta::SEOABLE_TYPES) as $t)
                    <option value="{{ $t }}" @selected(request('type') === $t)>{{ class_basename($t) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['type']))
                <a href="{{ route('admin.seo.index') }}"
                   class="ml-auto flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Meta Title</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Related To</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Updated</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($meta as $item)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.seo.show', $item) }}"
                                       class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                        {{ $item->meta_title ?? 'Untitled meta' }}
                                    </a>
                                    <p class="mt-0.5 truncate font-mono text-xs text-slate-400 dark:text-night-500">
                                        {{ class_basename($item->seoable_type) }} #{{ $item->seoable_id }}
                                    </p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ class_basename($item->seoable_type) }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">#{{ $item->seoable_id }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">{{ $item->updated_at->format('M d, Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.seo.show', $item) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('seo.edit')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.seo.destroy', $item) }}"
                                                  data-confirm="Delete this SEO meta?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                    @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                    Delete
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-400">No SEO meta found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $meta->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('seo.edit')
        <x-admin-modal id="create-seo-modal" title="Create SEO Meta">
            <form method="POST" action="{{ route('admin.seo.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-admin.form.select name="seoable_type" label="Type" id="seo-seoable-type">
                    <option value="">Select type</option>
                    @foreach(array_keys(\App\Models\SeoMeta::SEOABLE_TYPES) as $t)
                        <option value="{{ $t }}" @selected(old('seoable_type') === $t)>{{ class_basename($t) }}</option>
                    @endforeach
                </x-admin.form.select>
                <x-admin.form.select name="seoable_id" label="Related Item" id="seo-seoable-id" required
                    data-selected="{{ old('seoable_id') }}">
                    <option value="">Select a type first</option>
                </x-admin.form.select>
                <x-admin.form.input name="meta_title" label="Meta Title" :value="old('meta_title')" />
                <x-admin.form.textarea name="meta_description" label="Meta Description" rows="2">{{ old('meta_description') }}</x-admin.form.textarea>
                <x-admin.form.input name="og_title" label="OG Title" :value="old('og_title')" />
                <x-admin.form.textarea name="og_description" label="OG Description" rows="2">{{ old('og_description') }}</x-admin.form.textarea>
                <x-admin.form.input name="og_image" label="OG Image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hint="Maximum 2 MB. Recommended 1200×630." />
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-seo-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSelect = document.getElementById('seo-seoable-type');
                const idSelect = document.getElementById('seo-seoable-id');
                if (!typeSelect || !idSelect) return;

                function loadRecords(type, selectedId) {
                    if (!type) {
                        idSelect.innerHTML = '<option value="">Select a type first</option>';
                        return;
                    }
                    idSelect.innerHTML = '<option value="">Loading…</option>';
                    fetch('{{ route('admin.seo.records') }}?type=' + encodeURIComponent(type))
                        .then(r => r.json())
                        .then(data => {
                            idSelect.innerHTML = '<option value="">Select an item</option>';
                            data.records.forEach(rec => {
                                const opt = document.createElement('option');
                                opt.value = rec.id;
                                opt.textContent = rec.label + ' (#' + rec.id + ')';
                                if (String(rec.id) === String(selectedId)) opt.selected = true;
                                idSelect.appendChild(opt);
                            });
                        })
                        .catch(() => { idSelect.innerHTML = '<option value="">Failed to load items</option>'; });
                }

                typeSelect.addEventListener('change', () => loadRecords(typeSelect.value, ''));

                // Repopulate the item list after a validation error re-renders the form.
                if (typeSelect.value) {
                    loadRecords(typeSelect.value, idSelect.dataset.selected || '');
                }
            });
        </script>
        @endpush
    @endcan
</x-admin-layout>
