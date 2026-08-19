@props(['label' => null, 'name', 'type' => 'text', 'hint' => null, 'required' => false, 'error' => null])
@php $error ??= $errors->first($name); @endphp
<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $label }}@if($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif
    <input
        {{ $attributes->merge([
            'type' => $type,
            'id'   => $name,
            'name' => $name,
            'required' => $required,
            'class' => 'block w-full rounded-xl border px-3.5 py-2.5 text-sm transition
                focus:outline-none focus:ring-2 focus:ring-brand/10 focus:border-brand/50
                ' . ($error
                    ? 'border-rose-400 bg-rose-50 text-rose-900 placeholder-rose-400 dark:bg-rose-900/20 dark:border-rose-600 dark:text-rose-200'
                    : 'border-slate-200 bg-white text-slate-800 placeholder-slate-400
                       dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500'),
        ]) }}
    />
    @if ($error)
        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
