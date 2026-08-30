<x-admin-layout title="{{ ucfirst(str_replace('_', ' ', $matter->type)) }}">
    <x-admin-page-header title="{{ ucfirst(str_replace('_', ' ', $matter->type)) }}"
        :breadcrumbs="[['label' => 'Legal Matters', 'url' => route('admin.legal-matters.index')], ['label' => $matter->id]]"
        description="Resolve legal issues that may block deal progression." />

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Property</p>
            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $matter->deal?->listing?->title ?? '—' }}</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Deal #{{ $matter->deal_id }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</p>
            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                @if ($matter->status === 'cleared') bg-emerald-100 text-emerald-700
                @elseif ($matter->status === 'issue_found') bg-rose-100 text-rose-700
                @else bg-amber-100 text-amber-700 @endif">{{ ucfirst($matter->status) }}</span>
            @if ($matter->due_date)
                <p class="mt-2 text-xs text-slate-400">Due {{ $matter->due_date->format('d M Y') }}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Parties</p>
            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">Buyer: {{ $matter->deal?->buyer?->name ?? '—' }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Seller: {{ $matter->deal?->seller?->name ?? '—' }}</p>
        </div>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Notes</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $matter->notes ?: 'No notes yet.' }}</p>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 dark:border-night-700 dark:bg-night-900">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Update matter</h2>
        <form method="POST" action="{{ route('admin.legal-matters.update', $matter) }}" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            @method('PATCH')

            <div>
                <label class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</label>
                <select name="status"
                    class="mt-1.5 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                    @foreach (['pending', 'in_review', 'cleared', 'issue_found'] as $s)
                        <option value="{{ $s }}" @selected($matter->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Assignee</label>
                <select name="assigned_to"
                    class="mt-1.5 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                    <option value="">Unassigned</option>
                    @foreach ($reviewers as $reviewer)
                        <option value="{{ $reviewer->id }}" @selected($matter->assigned_to === $reviewer->id)>{{ $reviewer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Notes</label>
                <textarea name="notes" rows="3"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">{{ old('notes', $matter->notes) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">Save changes</button>
            </div>
        </form>
    </div>

    <a href="{{ route('admin.deals.show', $matter->deal_id) }}" class="text-sm font-medium text-brand hover:underline">← View related deal</a>
</x-admin-layout>