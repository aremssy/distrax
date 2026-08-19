@props(['type' => 'info', 'dismissible' => false, 'title' => null])
@php
$styles = [
    'success' => ['bg' => 'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30', 'text' => 'text-emerald-800 dark:text-emerald-300', 'icon' => 'check'],
    'error'   => ['bg' => 'bg-rose-50 border-rose-200 dark:bg-rose-500/10 dark:border-rose-500/30',             'text' => 'text-rose-800 dark:text-rose-300',       'icon' => 'exclamation-circle'],
    'warning' => ['bg' => 'bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/30',         'text' => 'text-amber-800 dark:text-amber-300',     'icon' => 'exclamation-circle'],
    'info'    => ['bg' => 'bg-sky-50 border-sky-200 dark:bg-sky-500/10 dark:border-sky-500/30',                 'text' => 'text-sky-800 dark:text-sky-300',         'icon' => 'information-circle'],
];
$s = $styles[$type] ?? $styles['info'];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 flex gap-3 items-start '.$s['bg']]) }}>
    @include('admin.partials.icon', ['name' => $s['icon'], 'class' => 'w-5 h-5 shrink-0 mt-0.5 '.$s['text']])
    <div class="min-w-0 flex-1 text-sm {{ $s['text'] }}">
        @if ($title)
            <p class="mb-0.5 font-semibold">{{ $title }}</p>
        @endif
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" onclick="this.closest('.rounded-xl').remove()" class="shrink-0 {{ $s['text'] }} opacity-60 hover:opacity-100">
            @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
        </button>
    @endif
</div>
