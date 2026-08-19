@props(['title' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900']) }}>
    @if ($title || isset($action))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-night-800">
            @if ($title)
                <h3 class="text-[15px] font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
            @endif
            @isset($action)
                <div class="shrink-0">{{ $action }}</div>
            @endisset
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
