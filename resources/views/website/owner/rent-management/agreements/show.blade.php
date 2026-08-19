@extends('website.layouts.dashboard')
@section('title', __('Agreement').' | '.config('app.name'))
@section('page-title', __('Agreement'))
@section('dashboard-content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><p class="min-w-0 text-sm text-slate-500">{{ $agreement->tenancy?->listing?->title }} &middot; {{ __('generated :date', ['date' => $agreement->generated_at->format('M j, Y')]) }}</p><a href="{{ route('owner.rent-management.tenancies.show', $agreement->tenancy) }}" class="shrink-0 text-sm font-semibold text-indigo-600">{{ __('Back to tenancy') }}</a></div>
    <article class="whitespace-pre-line rounded-2xl border border-slate-200 bg-white p-6 text-sm leading-relaxed text-slate-700">{{ $agreement->content }}</article>
</div>
@endsection
