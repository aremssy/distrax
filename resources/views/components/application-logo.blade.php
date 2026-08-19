@props(['alt' => config('app.name').' logo'])

@php
    $logo = setting('site_logo');
    $logoSrc = $logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($logo) : asset('assets/logo/logo.svg');
@endphp

<img
    src="{{ $logoSrc }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => 'h-auto w-auto']) }}
>
