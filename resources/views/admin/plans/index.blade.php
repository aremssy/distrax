<x-admin-layout title="Subscription Plans">
    <x-admin-page-header title="Subscription Plans"
        :breadcrumbs="[['label' => 'Subscription Plans']]"
        description="Manage subscription plans, features, and pricing.">
        <x-slot name="actions">
            <x-admin.export-button resource="plans" />
            @can('plans.edit')
                <button onclick="openModal('manage-features-modal')"
                    class="flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-night-700 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'adjustments', 'class' => 'w-4 h-4'])
                    Manage Features
                </button>
            @endcan
            @can('plans.create')
                <button onclick="openModal('create-plan-modal')"
                    class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Plan
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        @if ($plans->total() > 1)
            <div class="flex justify-end border-b border-slate-100 px-5 py-3 dark:border-night-800">@include('admin.partials.reorder-hint')</div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 px-3 py-3.5"></th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Plan</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Price</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Duration</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Post Limit</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Users</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800" data-sortable data-sortable-url="{{ route('admin.plans.reorder') }}" data-sortable-offset="{{ ($plans->currentPage() - 1) * $plans->perPage() }}">
                    @forelse($plans as $plan)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40" data-id="{{ $plan->id }}">
                            <td class="px-3 py-3.5 align-middle">@include('admin.partials.drag-handle')</td>
                            <td class="min-w-48 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $plan->name }}</span>
                                    @if($plan->is_featured)
                                        <x-admin-badge color="yellow">Featured</x-admin-badge>
                                    @endif
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">
                                    {{ $plan->description ?: 'Every '.$plan->duration_days.' days' }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ money($plan->price) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $plan->duration_days }} days
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $plan->post_limit === 0 ? 'Unlimited' : $plan->post_limit }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $plan->user_subscriptions_count ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$plan->is_active ? 'green' : 'gray'">{{ $plan->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @canany(['plans.edit', 'plans.delete'])
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            @can('plans.edit')
                                                <button type="button" onclick="openModal('edit-plan-{{ $plan->id }}')"
                                                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('admin.plans.toggle', $plan) }}">
                                                    @csrf @method('PATCH')
                                                    @if ($plan->is_active)
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
                                            @can('plans.delete')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                                                    data-confirm="Delete this plan?">
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
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-slate-400">No plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $plans->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Create Modal --}}
    @can('plans.create')
    <x-admin-modal id="create-plan-modal" title="Create Subscription Plan">
        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" required />
                <x-admin.form.input name="price" label="Price" type="number" min="0" required />
                <x-admin.form.input name="duration_days" label="Duration (Days)" type="number" min="1" max="3650" required />
                <x-admin.form.input name="post_limit" label="Post Limit" type="number" min="0" max="65535" required hint="0 = unlimited" />
                <div class="flex items-center gap-4 pt-6">
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Featured
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <x-admin.form.textarea name="description" label="Description" :rows="2"></x-admin.form.textarea>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Unlocked Features</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($available_features as $feature)
                        <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="unlocked_features[]" value="{{ $feature->key }}"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $feature->label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('create-plan-modal')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Create Plan
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endcan

    {{-- Edit Modals --}}
    @can('plans.edit')
    @foreach($plans as $plan)
    <x-admin-modal id="edit-plan-{{ $plan->id }}" title="Edit: {{ $plan->name }}">
        <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="name" label="Name" :value="old('name', $plan->name)" required />
                <x-admin.form.input name="price" label="Price" type="number" min="0" :value="old('price', $plan->price)" required />
                <x-admin.form.input name="duration_days" label="Duration (Days)" type="number" min="1" max="3650" :value="old('duration_days', $plan->duration_days)" required />
                <x-admin.form.input name="post_limit" label="Post Limit" type="number" min="0" max="65535" :value="old('post_limit', $plan->post_limit)" required hint="0 = unlimited" />
                <div class="flex items-center gap-4 pt-6">
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked($plan->is_featured)
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Featured
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($plan->is_active)
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Active
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <x-admin.form.textarea name="description" label="Description" :rows="2">{{ old('description', $plan->description) }}</x-admin.form.textarea>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Unlocked Features</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($available_features as $feature)
                        <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="unlocked_features[]" value="{{ $feature->key }}" @checked(in_array($feature->key, $plan->unlocked_features ?? []))
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            {{ $feature->label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('edit-plan-{{ $plan->id }}')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Update Plan
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endforeach
    @endcan

    {{-- Manage Features Modal --}}
    @can('plans.edit')
    <x-admin-modal id="manage-features-modal" title="Manage Plan Features">
        <div class="space-y-2">
            @forelse($available_features as $feature)
                <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3.5 py-2.5 dark:border-night-800">
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $feature->label }}</p>
                        <p class="text-xs text-slate-400 dark:text-night-500">{{ $feature->key }}</p>
                    </div>
                    @can('plans.delete')
                        <form method="POST" action="{{ route('admin.plan-features.destroy', $feature) }}"
                            data-confirm="Remove this feature? Plans referencing it will keep the data, but it will no longer be selectable.">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400">
                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                            </button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="text-sm text-slate-400">No features yet.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.plan-features.store') }}" class="mt-5 flex items-end gap-3 border-t border-slate-100 pt-4 dark:border-night-800">
            @csrf
            <div class="flex-1">
                <x-admin.form.input name="label" label="New Feature" placeholder="e.g. Featured Listings" required />
            </div>
            <button type="submit"
                class="px-4 py-2.5 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Add
            </button>
        </form>

        <x-slot name="footer">
            <button type="button" onclick="closeModal('manage-features-modal')"
                class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Close
            </button>
        </x-slot>
    </x-admin-modal>
    @endcan
</x-admin-layout>
