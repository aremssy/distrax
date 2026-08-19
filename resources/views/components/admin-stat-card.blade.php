@props(['label', 'value', 'icon' => 'chart-bar', 'color' => 'indigo', 'change' => null, 'changeLabel' => null])
@php
$colors = [
    'indigo'  => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400',
    'violet'  => 'bg-violet-50 text-violet-500 dark:bg-violet-500/15 dark:text-violet-400',
    'green'   => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400',
    'emerald' => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400',
    'red'     => 'bg-rose-50 text-rose-500 dark:bg-rose-500/15 dark:text-rose-400',
    'rose'    => 'bg-rose-50 text-rose-500 dark:bg-rose-500/15 dark:text-rose-400',
    'yellow'  => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400',
    'amber'   => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400',
    'orange'  => 'bg-orange-50 text-orange-400 dark:bg-orange-500/15 dark:text-orange-400',
    'blue'    => 'bg-blue-50 text-blue-500 dark:bg-blue-500/15 dark:text-blue-400',
    'sky'     => 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-400',
    'purple'  => 'bg-violet-50 text-violet-500 dark:bg-violet-500/15 dark:text-violet-400',
    'slate'   => 'bg-slate-100 text-slate-500 dark:bg-night-800 dark:text-slate-400',
];
$style = $colors[$color] ?? $colors['indigo'];
@endphp
<div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-night-700 dark:bg-night-900">
    <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $style }}">
            @include('admin.partials.icon', ['name' => $icon, 'class' => 'w-5.5 h-5.5'])
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-0.5 truncate text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>
        </div>
    </div>
    @if ($change !== null)
        <p class="mt-3.5 flex items-center gap-1.5 text-xs">
            @if ($change > 0)
                @include('admin.partials.icon', ['name' => 'arrow-trending-up', 'class' => 'w-3.5 h-3.5 text-emerald-500'])
                <span class="font-semibold text-emerald-500">{{ $change }}%</span>
            @elseif ($change < 0)
                @include('admin.partials.icon', ['name' => 'arrow-trending-down', 'class' => 'w-3.5 h-3.5 text-rose-500'])
                <span class="font-semibold text-rose-500">{{ abs($change) }}%</span>
            @else
                <span class="font-semibold text-slate-400">— 0%</span>
            @endif
            <span class="text-slate-400 dark:text-night-500">{{ $changeLabel }}</span>
        </p>
    @endif
</div>
