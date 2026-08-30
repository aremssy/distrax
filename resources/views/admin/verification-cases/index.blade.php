<x-admin-layout title="Verification Cases">
    <x-admin-page-header title="Verification Cases"
        :breadcrumbs="[['label' => 'Verification Cases']]"
        description="Distrax Verify case queue \u2014 property listings moving through the verification workflow." />

    <form method="GET" action="{{ route('admin.verification-cases.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <option value="">All statuses</option>
                @foreach (['in_progress', 'distrax_verified', 'disclosure_required', 'under_legal_review', 'not_verified'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>

            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="mine" value="1" onchange="this.form.submit()" @checked(request()->boolean('mine'))>
                Assigned to me
            </label>

            @if (request()->hasAny(['status', 'mine']))
                <a href="{{ route('admin.verification-cases.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">Clear</a>
            @endif
        </div>
    </form>

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="whitespace-nowrap px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listing</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Status</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Officer</th>
                        <th class="whitespace-nowrap px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Opened</th>
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($cases as $case)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-700 dark:text-slate-200">{{ $case->listing?->title ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5"><x-verification-badge :status="$case->status" /></td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 dark:text-slate-400">{{ $case->officer?->name ?? 'Unassigned' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 dark:text-slate-400">{{ $case->opened_at?->diffForHumans() }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <a href="{{ route('admin.verification-cases.show', $case) }}" class="text-sm font-medium text-brand hover:underline">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400 dark:text-night-500">No verification cases yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $cases->links() }}
</x-admin-layout>
