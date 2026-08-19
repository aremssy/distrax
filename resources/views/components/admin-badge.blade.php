@props(['color' => 'gray'])
@php
$colors = [
    'green'  => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
    'red'    => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
    'yellow' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
    'blue'   => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
    'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400',
    'gray'   => 'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300',
    'purple' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400',
    'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold '.($colors[$color] ?? $colors['gray'])]) }}>
    {{ $slot }}
</span>
