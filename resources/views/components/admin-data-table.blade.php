@props(['columns' => [], 'emptyMessage' => 'No records found.'])
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                @isset($head)
                    {{ $head }}
                @else
                    <tr>
                        @foreach ($columns as $col)
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">
                                {{ $col }}
                            </th>
                        @endforeach
                    </tr>
                @endisset
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                @isset($body)
                    {{ $body }}
                @else
                    {{ $slot }}
                @endisset
            </tbody>
        </table>

        @isset($empty)
            {{ $empty }}
        @else
            <div class="hidden only:flex items-center justify-center py-14 text-sm text-slate-400 dark:text-night-500">
                {{ $emptyMessage }}
            </div>
        @endisset
    </div>

    @isset($pagination)
        {{ $pagination }}
    @endisset
</div>
