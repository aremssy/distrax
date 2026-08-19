@extends('website.layouts.master')
@section('title', ($landingPage->headline ?: $zone->name).' | '.config('app.name'))
@push('head')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($landingPage->subheadline ?? $landingPage->headline ?? $zone->name), 155) }}">
    <link rel="canonical" href="{{ route('areas.show', $zone) }}">
    <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@section('content')
    @php
        $image = match (true) {
            ! $landingPage->featured_image => asset('assets/images/website/hero-section/hero-1.webp'),
            str_starts_with($landingPage->featured_image, 'http') => $landingPage->featured_image,
            default => asset('storage/'.$landingPage->featured_image),
        };
    @endphp
    <section class="relative flex min-h-72 items-center sm:min-h-[360px]">
        <div class="absolute inset-0 z-0">
            <img alt="{{ $zone->name }}" class="h-full w-full object-cover" src="{{ $image }}">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/55 to-slate-950/20"></div>
        </div>
        <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 text-white md:px-6">
            <h1 class="text-3xl font-bold sm:text-4xl md:text-5xl">{{ $landingPage->headline ?: __('Properties in :zone', ['zone' => $zone->name]) }}</h1>
            @if ($landingPage->subheadline)<p class="mt-4 max-w-2xl text-lg text-slate-200">{{ $landingPage->subheadline }}</p>@endif
        </div>
    </section>

    <main class="min-h-screen bg-slate-50 py-14">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            @if ($landingPage->content)
                <div class="prose prose-slate mb-10 max-w-none break-words [&_img]:h-auto [&_img]:max-w-full [&_pre]:overflow-x-auto [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto">{!! $landingPage->content !!}</div>
            @endif

            @if (! empty($landingPage->features))
                <div class="mb-10 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($landingPage->features as $feature)
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-700">{{ $feature }}</div>
                    @endforeach
                </div>
            @endif

            <h2 class="mb-6 text-2xl font-bold text-slate-950">{{ __('Properties in :zone', ['zone' => $zone->name]) }}</h2>
            @if ($listings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">{{ __('No active listings in :zone yet.', ['zone' => $zone->name]) }}</div>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($listings as $listing)
                        <x-website.property-card :$listing />
                    @endforeach
                </div>
                <div class="mt-8">{{ $listings->links() }}</div>
            @endif
        </div>
    </main>
@endsection
