<x-admin-layout title="Deals">
    <x-admin-page-header title="Deals"
        :breadcrumbs="[['label' => 'Deals']]"
        description="Offer acceptance through closing \u2014 every active transaction on the platform." />

    <form method="GET" action="{{ route('admin.deals.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="stage" onchange="this.form.submit()"
                    class="appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <option value="">All stages</option>
                @foreach (['offer_accepted', 'inspection', 'legal_review', 'closing', 'completed', 'fell_through'] as $s)
                    <option value="{{ $s }}" @selected(request('stage') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>

            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search listing / buyer / seller…"
                class="min-w-56 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 placeholder:text-slate-400 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">

            @if (request()->hasAny(['stage', 'q']))
                <a href="{{ route('admin.deals.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Clear</a>
            @endif
        </div>
    </form>

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listing</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Amount</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Buyer</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Stage</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($deals as $deal)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-700 dark:text-slate-200">{{ $deal->listing?->title ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 font-semibold text-slate-900 dark:text-slate-100">{{ money($deal->agreed_amount, $deal->currency_code) }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 dark:text-slate-400">{{ $deal->buyer?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    @if ($deal->stage === 'completed') bg-emerald-100 text-emerald-700
                                    @elseif ($deal->stage === 'fell_through') bg-rose-100 text-rose-700
                                    @else bg-indigo-100 text-indigo-700 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $deal->stage)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.deals.show', $deal) }}" class="text-sm font-medium text-brand hover:underline">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400 dark:text-night-500">No deals yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $deals->links() }}
</x-admin-layout>