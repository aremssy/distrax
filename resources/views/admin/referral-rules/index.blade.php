<x-admin-layout title="Referral Rules">
    <x-admin-page-header title="Referral Rules"
        :breadcrumbs="[['label' => 'Referral Rules']]"
        description="Manage referral reward rules.">
        <x-slot name="actions">
            <x-admin.export-button resource="referral-rules" />
            @can('marketing.edit')
                <button onclick="openModal('create-rule-modal')"
                        class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Create
                </button>
            @endcan
        </x-slot>
    </x-admin-page-header>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-10 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Name</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Reward Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Value</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Max Referrals</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($rules as $rule)
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $rule->id }}</td>

                            <td class="min-w-52 max-w-72 px-3 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.referral-rules.show', $rule) }}"
                                       class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                        {{ $rule->name }}
                                    </a>
                                    <p class="mt-0.5 truncate font-mono text-xs text-slate-400 dark:text-night-500">{{ $rule->slug }}</p>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $rule->reward_type)) }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-sm text-slate-700 dark:text-slate-200">{{ $rule->reward_value }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $rule->max_referrals ?? '∞' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$rule->is_active ? 'green' : 'gray'">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.referral-rules.show', $rule) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @can('marketing.edit')
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.referral-rules.destroy', $rule) }}"
                                                  data-confirm="Delete this rule?">
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
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No rules found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rules->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    @can('marketing.edit')
        <x-admin-modal id="create-rule-modal" title="Create Referral Rule">
            <form method="POST" action="{{ route('admin.referral-rules.store') }}" class="space-y-4">
                @csrf
                <x-admin.form.input name="name" label="Name" :value="old('name')" required />
                <x-admin.form.select name="reward_type" label="Reward Type">
                    <option value="credit">Credit</option>
                    <option value="discount_percent">Discount Percent</option>
                    <option value="discount_fixed">Discount Fixed</option>
                    <option value="free_listing">Free Listing</option>
                    <option value="featured_listing">Featured Listing</option>
                </x-admin.form.select>
                <x-admin.form.input name="reward_value" label="Reward Value" type="number" :value="old('reward_value')" required />
                <x-admin.form.textarea name="reward_description" label="Description" rows="2">{{ old('reward_description') }}</x-admin.form.textarea>
                <x-admin.form.input name="max_referrals" label="Max Referrals" type="number" :value="old('max_referrals')" hint="Leave empty for unlimited" />
                <x-admin.form.input name="min_purchase_amount" label="Min Purchase Amount" type="number" :value="old('min_purchase_amount', 0)" />
                <x-admin.form.input name="starts_at" label="Starts At" type="date" :value="old('starts_at')" />
                <x-admin.form.input name="expires_at" label="Expires At" type="date" :value="old('expires_at')" />
                <x-admin.form.select name="is_active" label="Status">
                    <option value="1" @selected(old('is_active', true))>Active</option>
                    <option value="0">Inactive</option>
                </x-admin.form.select>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('create-rule-modal')" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-night-700 rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">Create</button>
                </div>
            </form>
        </x-admin-modal>
    @endcan
</x-admin-layout>
