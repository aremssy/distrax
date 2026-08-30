<x-admin-layout title="Legal Matters">
    <x-admin-page-header title="Legal Matters"
        :breadcrumbs="[['label' => 'Legal Matters']]"
        description="Issues, review queues and cleared items across active deals." />

    <form method="GET" action="{{ route('admin.legal-matters.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <option value="">All statuses</option>
                @foreach (['pending', 'in_review', 'cleared', 'issue_found'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>

            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="open" value="1" onchange="this.form.submit()" @checked(request()->boolean('open'))>
                Open only
            </label>

            @if (request()->hasAny(['status', 'open']))
                <a href="{{ route('admin.legal-matters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Clear</a>
            @endif
        </div>
    </form>

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Type</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Deal / Property</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Assignee</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($matters as $matter)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-700 dark:text-slate-200">{{ ucfirst(str_replace('_', ' ', $matter->type)) }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 dark:text-slate-400">{{ $matter->deal?->listing?->title ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    @if ($matter->status === 'cleared') bg-emerald-100 text-emerald-700
                                    @elseif ($matter->status === 'issue_found') bg-rose-100 text-rose-700
                                    @else bg-amber-100 text-amber-700 @endif">{{ ucfirst($matter->status) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 dark:text-slate-400">{{ $matter->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.legal-matters.show', $matter) }}" class="text-sm font-medium text-brand hover:underline">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400 dark:text-night-500">No legal matters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $matters->links() }}
</x-admin-layout>