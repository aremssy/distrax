{{--
    Injects the admin-configured brand colours as runtime CSS custom properties.
    Overriding the indigo scale (and the brand/accent tokens) here cascades to
    every `*-indigo-*` utility and semantic token compiled by Tailwind, so the
    Branding settings actually re-skin the whole site. Rendered after the Vite
    stylesheet so these `:root` values win by source order.
--}}
@php
    $hex = fn (mixed $value, string $fallback): string => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $value) ? (string) $value : $fallback;
    $brandPrimary = $hex(setting('primary_color'), '#5352ED');
    $brandSecondary = $hex(setting('secondary_color'), '#0EA5E9');
    $palette = brand_palette($brandPrimary);
@endphp
<style>
    :root {
        @foreach ($palette as $step => $value)
        --color-indigo-{{ $step }}: {{ $value }};
        @endforeach
        --color-brand: {{ $brandPrimary }};
        --site-accent: {{ $brandPrimary }};
        --color-brand-secondary: {{ $brandSecondary }};
    }
</style>
