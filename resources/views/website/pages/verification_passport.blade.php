@extends('website.layouts.master')

@section('title', 'Verification Passport '.$score->reference_id.' | '.config('app.name'))

@push('head')
    <meta name="description" content="Distrax verification passport {{ $score->reference_id }}.">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-night-700 dark:bg-night-900">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Verification Passport</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $score->reference_id }}</h1>

            @if ($score->listing)
                <p class="mt-1 text-slate-500 dark:text-slate-400">{{ $score->listing->title }}</p>
            @endif

            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                Verified {{ $score->verification_date->format('M j, Y') }}
                @if ($score->expiry_review_date)
                    &middot; next review {{ $score->expiry_review_date->format('M j, Y') }}
                @endif
            </p>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-400">Verification score</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->score }}/100</dd></div>
                <div><dt class="text-slate-400">Seller verification</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->seller_verification_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Title</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->title_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Ownership</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->ownership_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Survey</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->survey_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Physical inspection</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->physical_inspection_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Legal review</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->legal_review_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Planning</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->planning_status ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Disclosures on file</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $score->disclosure_count }}</dd></div>
            </dl>

            <p class="mt-8 text-xs text-slate-400">
                This passport reflects Distrax's defined verification process as of the date above. It is not a
                guarantee that a transaction on this property cannot fail.
            </p>
        </div>
    </div>
@endsection
