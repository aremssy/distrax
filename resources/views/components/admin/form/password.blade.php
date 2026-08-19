@props(['label' => null, 'name', 'hint' => null, 'required' => false, 'error' => null])
@php $error ??= $errors->first($name); @endphp
<div x-data="{ show: false }">
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}@if($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif
    <div class="relative">
        <input
            {{ $attributes->merge([
                'id'   => $name,
                'name' => $name,
                'required' => $required,
                'autocomplete' => 'off',
                'class' => 'block w-full rounded-xl border px-3.5 py-2.5 pr-11 text-sm transition
                    focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand/50
                    ' . ($error
                        ? 'border-rose-400 bg-rose-50 text-rose-900 placeholder-rose-400 dark:bg-rose-900/20 dark:border-rose-600 dark:text-rose-200'
                        : 'border-slate-200 bg-white text-slate-800 placeholder-slate-400
                           dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500'),
            ]) }}
            :type="show ? 'text' : 'password'"
        />
        <button type="button" @click="show = !show" tabindex="-1"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                :aria-label="show ? 'Hide {{ $label ?? 'password' }}' : 'Show {{ $label ?? 'password' }}'">
            <span x-show="!show">@include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])</span>
            <span x-show="show" x-cloak>@include('admin.partials.icon', ['name' => 'eye-slash', 'class' => 'w-4 h-4'])</span>
        </button>
    </div>
    @if ($error)
        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
