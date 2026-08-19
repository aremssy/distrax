<x-admin-layout title="Wallet #{{ $wallet->id }}">
    <x-admin-page-header title="Wallet: {{ $wallet->user?->name ?? 'Unknown' }}"
        :breadcrumbs="[
            ['route' => 'admin.wallets.index', 'label' => 'Wallets'],
            ['label' => 'Wallet #' . $wallet->id],
        ]"
        description="Balance: {{ money($wallet->balance, $wallet->currency) }}">

        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.wallets.adjust', $wallet->user) }}" class="inline-flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    @csrf
                    <input type="hidden" name="type" value="credit">
                    <x-admin.form.input name="amount" type="number" min="1" placeholder="Amount" class="w-full sm:w-28" />
                    <x-admin.form.input name="description" placeholder="Description" class="w-full sm:w-40" />
                    <button type="submit"
                        class="w-full sm:w-auto px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Credit
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.wallets.adjust', $wallet->user) }}" class="inline-flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    @csrf
                    <input type="hidden" name="type" value="debit">
                    <x-admin.form.input name="amount" type="number" min="1" placeholder="Amount" class="w-full sm:w-28" />
                    <x-admin.form.input name="description" placeholder="Description" class="w-full sm:w-40" />
                    <button type="submit"
                        class="w-full sm:w-auto px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        Debit
                    </button>
                </form>
            </div>
        </x-slot>
    </x-admin-page-header>

    {{-- Wallet Info --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-admin-card title="Current Balance">
            <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ money($wallet->balance, $wallet->currency) }}</p>
        </x-admin-card>
        <x-admin-card title="User">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $wallet->user?->name }}</p>
            <p class="break-all text-xs text-slate-400 sm:break-normal">{{ $wallet->user?->email }}</p>
        </x-admin-card>
        <x-admin-card title="Currency">
            <p class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $wallet->currency }}</p>
        </x-admin-card>
    </div>

    {{-- Transactions --}}
    <x-admin-card title="Transaction History">
        <div class="overflow-x-auto -mx-5 -mb-5">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Date</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Amount</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Balance After</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-night-800/40">
                            <td class="px-5 py-2.5 text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $txn->created_at->format('M j, Y g:i A') }}
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                <x-admin-badge :color="$txn->type === 'credit' ? 'green' : 'red'">
                                    {{ ucfirst($txn->type) }}
                                </x-admin-badge>
                            </td>
                            <td class="px-5 py-2.5 font-mono font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                {{ money($txn->amount, $wallet->currency) }}
                            </td>
                            <td class="px-5 py-2.5 font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ money($txn->balance_after, $wallet->currency) }}
                            </td>
                            <td class="px-5 py-2.5 text-xs text-slate-600 dark:text-slate-400">
                                {{ $txn->description }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">No transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

                {{ $transactions->onEachSide(1)->links('admin.partials.pagination') }}
    </x-admin-card>
</x-admin-layout>
