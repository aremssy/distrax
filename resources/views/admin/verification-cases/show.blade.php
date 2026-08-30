<x-admin-layout title="Verification Case #{{ $case->id }}">
    <x-admin-page-header title="Verification Case #{{ $case->id }}"
        :breadcrumbs="[['label' => 'Verification Cases', 'url' => route('admin.verification-cases.index')], ['label' => '#'.$case->id]]"
        :description="$case->listing?->title" />

    <div class="mb-6 flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-night-700 dark:bg-night-900">
        <x-verification-badge :status="$case->status" />

        <form method="POST" action="{{ route('admin.verification-cases.assign', $case) }}" class="flex items-center gap-2">
            @csrf @method('PATCH')
            <select name="officer_id" onchange="this.form.submit()"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                <option value="">Assign officer\u2026</option>
                @foreach ($officers as $officer)
                    <option value="{{ $officer->id }}" @selected($case->assigned_officer_id === $officer->id)>{{ $officer->name }}</option>
                @endforeach
            </select>
        </form>

        @if ($case->expiry_review_date)
            <span class="text-sm text-slate-500 dark:text-slate-400">Review by {{ $case->expiry_review_date->format('M j, Y') }}</span>
        @endif
    </div>

    <div class="space-y-4">
        @foreach ($case->tasks as $task)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-night-700 dark:bg-night-900">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ ucwords(str_replace('_', ' ', $task->layer)) }}</p>
                        <p class="text-xs uppercase tracking-widest text-slate-400 dark:text-night-500">{{ ucwords(str_replace('_', ' ', $task->owner_role)) }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-medium
                        @class([
                            'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300' => $task->status === 'not_started',
                            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300' => $task->status === 'in_progress',
                            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300' => $task->status === 'passed',
                            'bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300' => $task->status === 'failed',
                            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300' => $task->status === 'flagged',
                        ])">
                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                    </span>
                </div>

                @if ($task->notes)
                    <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">{{ $task->notes }}</p>
                @endif

                @if ($task->evidence->isNotEmpty())
                    <ul class="mb-3 space-y-1 text-sm">
                        @foreach ($task->evidence as $evidence)
                            <li>
                                <a href="{{ route('admin.verification-cases.evidence.file', $evidence) }}" class="text-brand hover:underline">{{ $evidence->type }}</a>
                                @if ($evidence->description) &mdash; {{ $evidence->description }} @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('admin.verification-cases.tasks.update', [$case, $task]) }}" class="flex flex-wrap items-end gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                        @foreach (['not_started', 'in_progress', 'passed', 'failed', 'flagged'] as $status)
                            <option value="{{ $status }}" @selected($task->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="notes" placeholder="Notes / waiver reason" value="{{ $task->notes }}"
                           class="min-w-52 flex-1 rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                    <label class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <input type="checkbox" name="waived" value="1"> Waive
                    </label>
                    <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand/90">Save</button>
                </form>

                <form method="POST" action="{{ route('admin.verification-cases.tasks.evidence', [$case, $task]) }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="text" name="type" placeholder="Evidence type" required
                           class="rounded-xl border border-slate-200 bg-white py-2 px-3 text-sm dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                    <input type="file" name="file" required class="text-sm">
                    <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:text-slate-300">Upload</button>
                </form>
            </div>
        @endforeach
    </div>
</x-admin-layout>
