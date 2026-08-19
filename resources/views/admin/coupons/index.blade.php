<x-admin-layout title="Coupons">
    <x-admin-page-header title="Coupons"
        :breadcrumbs="[['label' => 'Coupons']]"
        description="Manage discount coupons for subscriptions and packages.">
        <x-slot name="actions">
            <x-admin.export-button resource="coupons" />
            @can('coupons.create')
                <button type="button" onclick="openModal('create-coupon-modal')"
                    class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create Coupon
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.coupons.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by code..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="active" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Statuses</option>
                <option value="1" @selected(request('active') === '1')>Active</option>
                <option value="0" @selected(request('active') === '0')>Inactive</option>
            </select>

            @if (request()->hasAny(['search', 'active']))
                <a href="{{ route('admin.coupons.index') }}"
                   class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
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
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Code</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Value</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Usage</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Valid Period</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($coupons as $coupon)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $coupon->id }}</td>

                            <td class="px-3 py-3.5">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $coupon->code }}</p>
                                    @if ($coupon->applicable_for)
                                        <p class="truncate text-xs text-slate-400 dark:text-night-500">{{ ucwords(str_replace('_', ' ', $coupon->applicable_for)) }}</p>
                                    @endif
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$coupon->type === 'percentage' ? 'blue' : 'purple'">{{ ucfirst($coupon->type) }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $coupon->type === 'percentage' ? $coupon->value . '%' : money($coupon->value) }}</span>
                                @if ($coupon->max_discount)
                                    <span class="text-xs text-slate-400 dark:text-night-500">(max {{ money($coupon->max_discount) }})</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm text-slate-600 dark:text-slate-300">
                                    @if ($coupon->max_uses)
                                        {{ $coupon->payments_count ?? 0 }}/{{ $coupon->max_uses }}
                                    @else
                                        {{ $coupon->payments_count ?? 0 }}
                                    @endif
                                </span>
                                @if ($coupon->max_uses_per_user)
                                    <p class="text-xs text-slate-400 dark:text-night-500">per user: {{ $coupon->max_uses_per_user }}</p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @if ($coupon->starts_at)
                                    <p class="text-sm text-slate-600 dark:text-slate-300">From: {{ $coupon->starts_at->format('M j, Y') }}</p>
                                @endif
                                @if ($coupon->expires_at)
                                    <p class="text-xs text-slate-400 dark:text-night-500">Until: {{ $coupon->expires_at->format('M j, Y') }}</p>
                                @endif
                                @if (! $coupon->starts_at && ! $coupon->expires_at)
                                    <span class="text-sm text-slate-400 dark:text-night-500">No expiry</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$coupon->is_active ? 'green' : 'gray'">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @canany(['coupons.edit', 'coupons.delete'])
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            @can('coupons.edit')
                                                <button type="button" onclick="openModal('edit-coupon-{{ $coupon->id }}')"
                                                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </button>
                                            @endcan

                                            @can('coupons.delete')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                                      data-confirm="Delete this coupon?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-slate-400">No coupons found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $coupons->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Create Modal --}}
    @can('coupons.create')
    <x-admin-modal id="create-coupon-modal" title="Create Coupon">
        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="code" label="Code" required hint="Will be uppercased" />
                <x-admin.form.select name="type" label="Type" required>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed</option>
                </x-admin.form.select>
                <x-admin.form.input name="value" label="Value" type="number" min="1" required hint="Amount or percentage" />
                <x-admin.form.input name="max_discount" label="Max Discount" type="number" min="0" hint="Leave empty for no limit" />
                <x-admin.form.input name="min_order" label="Min Order" type="number" min="0" hint="Minimum purchase amount" />
                <x-admin.form.input name="max_uses" label="Max Uses" type="number" min="1" max="65535" hint="Leave empty for unlimited" />
                <x-admin.form.input name="max_uses_per_user" label="Max Uses Per User" type="number" min="1" max="255" required />
                <x-admin.form.select name="applicable_for" label="Applicable For">
                    <option value="">All</option>
                    <option value="subscription">Subscription</option>
                    <option value="listing_package">Listing Package</option>
                </x-admin.form.select>
                <x-admin.form.input name="starts_at" label="Starts At" type="date" />
                <x-admin.form.input name="expires_at" label="Expires At" type="date" />
                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('create-coupon-modal')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Create Coupon
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endcan

    {{-- Edit Modals --}}
    @can('coupons.edit')
    @foreach($coupons as $coupon)
    <x-admin-modal id="edit-coupon-{{ $coupon->id }}" title="Edit: {{ $coupon->code }}">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-admin.form.input name="code" label="Code" :value="old('code', $coupon->code)" required />
                <x-admin.form.select name="type" label="Type" required>
                    <option value="percentage" @selected($coupon->type === 'percentage')>Percentage</option>
                    <option value="fixed" @selected($coupon->type === 'fixed')>Fixed</option>
                </x-admin.form.select>
                <x-admin.form.input name="value" label="Value" type="number" min="1" :value="old('value', $coupon->value)" required />
                <x-admin.form.input name="max_discount" label="Max Discount" type="number" min="0" :value="old('max_discount', $coupon->max_discount)" />
                <x-admin.form.input name="min_order" label="Min Order" type="number" min="0" :value="old('min_order', $coupon->min_order)" />
                <x-admin.form.input name="max_uses" label="Max Uses" type="number" min="1" max="65535" :value="old('max_uses', $coupon->max_uses)" />
                <x-admin.form.input name="max_uses_per_user" label="Max Uses Per User" type="number" min="1" max="255" :value="old('max_uses_per_user', $coupon->max_uses_per_user)" required />
                <x-admin.form.select name="applicable_for" label="Applicable For">
                    <option value="">All</option>
                    <option value="subscription" @selected($coupon->applicable_for === 'subscription')>Subscription</option>
                    <option value="listing_package" @selected($coupon->applicable_for === 'listing_package')>Listing Package</option>
                </x-admin.form.select>
                <x-admin.form.input name="starts_at" label="Starts At" type="date" :value="old('starts_at', $coupon->starts_at?->format('Y-m-d'))" />
                <x-admin.form.input name="expires_at" label="Expires At" type="date" :value="old('expires_at', $coupon->expires_at?->format('Y-m-d'))" />
                <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-600 dark:text-slate-400 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($coupon->is_active)
                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
            </div>

            <x-slot name="footer">
                <button type="button" onclick="closeModal('edit-coupon-{{ $coupon->id }}')"
                    class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Update Coupon
                </button>
            </x-slot>
        </form>
    </x-admin-modal>
    @endforeach
    @endcan
</x-admin-layout>
