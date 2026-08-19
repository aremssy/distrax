<x-admin-layout title="Payouts">
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

    <x-admin-page-header title="Payouts"
        :breadcrumbs="[['label' => 'Payouts']]"
        description="Manage payouts to users.">
        <x-slot name="actions">
            <x-admin.export-button resource="payouts" />
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.payouts.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Statuses</option>
                @foreach (['pending', 'processing', 'completed', 'failed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="gateway" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Gateways</option>
                @foreach (['bkash', 'nagad', 'rocket', 'bank'] as $g)
                    <option value="{{ $g }}" @selected(request('gateway') === $g)>{{ ucfirst($g) }}</option>
                @endforeach
            </select>

            @if (request()->hasAny(['status', 'gateway']))
                <a href="{{ route('admin.payouts.index') }}"
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
                        <th class="w-12 whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">#</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">User</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Amount</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Gateway</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Account</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Processed By</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Date</th>
                        <th class="whitespace-nowrap px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($payouts as $payout)
                        @php
                            $avatarStyle = $avatarStyles[crc32($payout->user?->name ?? '—') % count($avatarStyles)];
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $payout->id }}</td>

                            <td class="px-3 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($payout->user?->name ?? '—', 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $payout->user?->name ?? '—' }}</p>
                                        <p class="truncate text-xs text-slate-400 dark:text-night-500">{{ $payout->user?->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ money($payout->amount, $payout->currency) }}</span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ ucfirst($payout->gateway) }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs text-slate-400 dark:text-night-500">{{ $payout->account_ref ?? '—' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <x-admin-badge :color="$payout->status === 'completed' ? 'green' : ($payout->status === 'failed' ? 'red' : ($payout->status === 'processing' ? 'blue' : 'yellow'))">
                                    {{ ucfirst($payout->status) }}
                                </x-admin-badge>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">{{ $payout->processedBy?->name ?? '—' }}</td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ $payout->created_at->format('M j, Y') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                @if (in_array($payout->status, ['pending', 'processing']))
                                    <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                        <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                                class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                            @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                        </button>
                                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                            <form method="POST" action="{{ route('admin.payouts.status', $payout) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'banknotes', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Process
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.payouts.status', $payout) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                    @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                                                    Complete
                                                </button>
                                            </form>
                                            <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                            <form method="POST" action="{{ route('admin.payouts.status', $payout) }}"
                                                  data-confirm="Mark this payout as failed?">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="failed">
                                                <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                    @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                                    Fail
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-night-500">{{ ucfirst($payout->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center text-sm text-slate-400">No payouts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $payouts->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
