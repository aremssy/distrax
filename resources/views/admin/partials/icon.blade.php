@php $icons = \App\Services\AdminIcons::PATHS; @endphp
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $class ?? 'w-5 h-5' }}">
    @isset($icons[$name])
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$name] }}" />
    @endisset
</svg>
