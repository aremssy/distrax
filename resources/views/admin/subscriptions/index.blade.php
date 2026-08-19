<x-admin-layout title="User Subscriptions">
    @php
        $avatarStyles = [
            'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
            'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        ];
    @endphp

    <x-admin-page-header title="User Subscriptions"
        :breadcrumbs="[['label' => 'User Subscriptions']]"
        description="Manage user subscriptions, extensions, and cancellations.">
        <x-slot name="actions">
            <x-admin.export-button resource="subscriptions" />
            <a href="{{ route('admin.plans.index') }}"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'credit-card', 'class' => 'w-4 h-4 text-slate-400'])
                Manage Plans
            </a>
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.subscriptions.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search subscribers..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All statuses</option>
                @foreach (['active', 'expired', 'cancelled', 'refunded'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status', 'search']))
                <a href="{{ route('admin.subscriptions.index') }}"
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
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Subscriber</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Plan</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Period</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Posts Used</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Auto Renew</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($subscriptions as $sub)
                        @php
                            $subscriberName = $sub->user?->name ?? '—';
                            $avatarStyle = $avatarStyles[crc32($subscriberName) % count($avatarStyles)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="min-w-60 max-w-72 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($subscriberName, 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $subscriberName }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-night-500">{{ $sub->user?->email ?? '#'.$sub->id }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $sub->plan?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                <div>{{ $sub->starts_at?->format('M j, Y') }}</div>
                                <div class="text-xs text-slate-400 dark:text-night-500">→ {{ $sub->ends_at?->format('M j, Y') ?? '∞' }}</div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $sub->posts_used ?? 0 }}{{ $sub->plan ? '/'.($sub->plan->post_limit === 0 ? '∞' : $sub->plan->post_limit) : '' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$sub->status === 'active' ? 'green' : ($sub->status === 'expired' ? 'gray' : ($sub->status === 'cancelled' ? 'red' : 'yellow'))">
                                    {{ ucfirst($sub->status) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                @if ($sub->auto_renew)
                                    <x-admin-badge color="green">Yes</x-admin-badge>
                                @else
                                    <span class="text-sm text-slate-300 dark:text-night-500">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @if ($sub->status === 'active')
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            <button type="button" onclick="openModal('extend-sub-{{ $sub->id }}')"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                @include('admin.partials.icon', ['name' => 'calendar-days', 'class' => 'w-4 h-4 text-slate-400'])
                                                Extend
                                            </button>

                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.subscriptions.cancel', $sub) }}"
                                                  data-confirm="Cancel this subscription?">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-slate-300 dark:text-night-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-400">No subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $subscriptions->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Extend Modals --}}
    @foreach ($subscriptions as $sub)
        @if ($sub->status === 'active')
            <x-admin-modal id="extend-sub-{{ $sub->id }}" title="Extend Subscription #{{ $sub->id }}">
                <form method="POST" action="{{ route('admin.subscriptions.extend', $sub) }}">
                    @csrf @method('PATCH')
                    <x-admin.form.input name="days" label="Extend by (days)" type="number" min="1" max="3650" required />
                    <x-slot name="footer">
                        <button type="button" onclick="closeModal('extend-sub-{{ $sub->id }}')"
                            class="px-4 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                            Extend
                        </button>
                    </x-slot>
                </form>
            </x-admin-modal>
        @endif
    @endforeach
</x-admin-layout>
