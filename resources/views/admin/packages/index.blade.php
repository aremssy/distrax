<x-admin-layout title="Listing Packages">
    <x-admin-page-header title="Listing Packages"
        :breadcrumbs="[['label' => 'Listing Packages']]"
        description="Manage listing packages for posting properties.">
        <x-slot name="actions">
            <x-admin.export-button resource="packages" />
            @can('listing_packages.create')
                <button onclick="openModal('create-package-modal')"
                    class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Package
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        @if ($packages->total() > 1)
            <div class="flex justify-end border-b border-slate-100 px-5 py-3 dark:border-night-800">@include('admin.partials.reorder-hint')</div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 px-3 py-3.5"></th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Package</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Price</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Post Quota</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Duration</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Features</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800" data-sortable data-sortable-url="{{ route('admin.packages.reorder') }}" data-sortable-offset="{{ ($packages->currentPage() - 1) * $packages->perPage() }}">
                    @forelse($packages as $pkg)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40" data-id="{{ $pkg->id }}">
                            <td class="px-3 py-3.5 align-middle">@include('admin.partials.drag-handle')</td>
                            <td class="min-w-44 max-w-72 px-5 py-3.5">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $pkg->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $pkg->post_quota }} posts / {{ $pkg->duration_days }} days</p>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ money($pkg->price) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $pkg->post_quota }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $pkg->duration_days }} days
                            </td>
                            <td class="px-3 py-3.5">
                                <div class="flex max-w-64 flex-wrap gap-1">
                                    @forelse(($pkg->features ?? []) as $feature)
                                        <x-admin-badge color="indigo">{{ $feature }}</x-admin-badge>
                                    @empty
                                        <span class="text-xs text-slate-400 dark:text-night-500">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$pkg->is_active ? 'green' : 'gray'">{{ $pkg->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @canany(['listing_packages.edit', 'listing_packages.delete'])
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            @can('listing_packages.edit')
                                                <button type="button" onclick="openModal('edit-package-{{ $pkg->id }}')"
                                                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('admin.packages.toggle', $pkg) }}">
                                                    @csrf @method('PATCH')
                                                    @if ($pkg->is_active)
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                            @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4 text-slate-400'])
                                                            Deactivate
                                                        </button>
                                                    @else
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                            @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                                                            Activate
                                                        </button>
                                                    @endif
                                                </form>
                                            @endcan
                                            @can('listing_packages.delete')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.packages.destroy', $pkg) }}"
                                                    data-confirm="Delete this package?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-slate-400">No packages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $packages->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Create Modal --}}
    @can('listing_packages.create')
    <x-admin-modal id="create-package-modal" title="Create Listing Package">
        <form method="POST" action="{{ route('admin.packages.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" required />
                <x-admin.form.input name="price" label="Price" type="number" min="0" required />
                <x-admin.form.input name="post_quota" label="Post Quota" type="number" min="1" max="65535" required />
                <x-admin.form.input name="duration_days" label="Duration (Days)" type="number" min="1" max="3650" required />
                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Features</label>
                <div id="create-features-list" class="space-y-2">
                    <div class="flex items-center gap-2">
                        <x-admin.form.input name="features[]" placeholder="e.g. Featured Listing" />
                    </div>
                </div>
                <button type="button" onclick="addFeature('create')"
                    class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                    + Add another feature
                </button>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('create-package-modal')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Create Package
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endcan

    {{-- Edit Modals --}}
    @can('listing_packages.edit')
    @foreach($packages as $pkg)
    <x-admin-modal id="edit-package-{{ $pkg->id }}" title="Edit: {{ $pkg->name }}">
        <form method="POST" action="{{ route('admin.packages.update', $pkg) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" :value="old('name', $pkg->name)" required />
                <x-admin.form.input name="price" label="Price" type="number" min="0" :value="old('price', $pkg->price)" required />
                <x-admin.form.input name="post_quota" label="Post Quota" type="number" min="1" max="65535" :value="old('post_quota', $pkg->post_quota)" required />
                <x-admin.form.input name="duration_days" label="Duration (Days)" type="number" min="1" max="3650" :value="old('duration_days', $pkg->duration_days)" required />
                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($pkg->is_active)
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Features</label>
                <div id="edit-features-{{ $pkg->id }}" class="space-y-2">
                    @forelse(($pkg->features ?? ['']) as $feature)
                        <div class="flex items-center gap-2">
                            <x-admin.form.input name="features[]" :value="$feature" placeholder="e.g. Featured Listing" />
                        </div>
                    @empty
                        <div class="flex items-center gap-2">
                            <x-admin.form.input name="features[]" placeholder="e.g. Featured Listing" />
                        </div>
                    @endforelse
                </div>
                <button type="button" onclick="addFeature('edit-{{ $pkg->id }}')"
                    class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                    + Add another feature
                </button>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('edit-package-{{ $pkg->id }}')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Update Package
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endforeach
    @endcan

    @push('scripts')
    <script>
        function addFeature(prefix) {
            const container = document.getElementById(prefix === 'create' ? 'create-features-list' : 'edit-features-' + prefix);
            if (!container) return;
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = '<input type="text" name="features[]" class="block w-full rounded-lg border border-slate-200 dark:border-night-700 bg-white dark:bg-night-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:placeholder-night-500" placeholder="e.g. Featured Listing" />';
            container.appendChild(div);
        }
    </script>
    @endpush
</x-admin-layout>
