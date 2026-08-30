<x-admin-layout title="Deal #{{ $deal->id }}">
    <x-admin-page-header title="Deal #{{ $deal->id }}"
        :breadcrumbs="[['label' => 'Deals', 'url' => route('admin.deals.index')], ['label' => '#'.$deal->id]]"
        description="Manage a transaction from offer acceptance through closing." />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-300">{{ session('error') }}</div>
    @endif

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Property</p>
            <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ $deal->listing?->title ?? '—' }}</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $deal->listing?->address }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Parties</p>
            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">Buyer: {{ $deal->buyer?->name ?? '—' }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Seller: {{ $deal->seller?->name ?? '—' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Agreed amount</p>
            <p class="mt-1 text-2xl font-bold text-brand">{{ money($deal->agreed_amount, $deal->currency_code) }}</p>
            <span class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                @if ($deal->stage === 'completed') bg-emerald-100 text-emerald-700
                @elseif ($deal->stage === 'fell_through') bg-rose-100 text-rose-700
                @else bg-indigo-100 text-indigo-700 @endif">
                {{ ucfirst(str_replace('_', ' ', $deal->stage)) }}
            </span>
        </div>
    </div>

    @unless (in_array($deal->stage, ['completed', 'fell_through'], true))
        <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Advance stage</h2>
            <form method="POST" action="{{ route('admin.deals.advance', $deal) }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <select name="stage" class="appearance-none rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                    @foreach (['offer_accepted', 'inspection', 'legal_review', 'closing', 'completed'] as $s)
                        <option value="{{ $s }}" @selected($deal->stage === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">Advance</button>
            </form>
        </div>
    @endunless

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="border-b border-slate-100 px-6 py-4 dark:border-night-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Legal matters</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($deal->legalMatters as $matter)
                        <tr>
                            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-200">{{ ucfirst(str_replace('_', ' ', $matter->type)) }}</td>
                            <td class="px-3 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    @if ($matter->status === 'cleared') bg-emerald-100 text-emerald-700
                                    @elseif ($matter->status === 'issue_found') bg-rose-100 text-rose-700
                                    @else bg-amber-100 text-amber-700 @endif">{{ ucfirst($matter->status) }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.legal-matters.show', $matter) }}" class="text-sm font-medium text-brand hover:underline">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-slate-400 dark:text-night-500">No legal matters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @unless ($deal->stage === 'fell_through')
        <div class="rounded-2xl border border-rose-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <h2 class="text-sm font-bold text-rose-700 dark:text-rose-300">Mark deal as fell through</h2>
            <form method="POST" action="{{ route('admin.deals.cancel', $deal) }}" class="mt-3 flex flex-wrap items-end gap-3"
                onsubmit="return confirm('Mark this deal as fell through?')">
                @csrf
                @method('PATCH')
                <input type="text" name="reason" placeholder="Optional reason…"
                    class="min-w-64 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 placeholder:text-slate-400 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <button type="submit" class="rounded-xl border border-rose-200 px-5 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Cancel deal</button>
            </form>
        </div>
    @endunless
</x-admin-layout>