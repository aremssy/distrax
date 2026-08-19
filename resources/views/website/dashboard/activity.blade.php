@extends('website.layouts.dashboard')
@section('title', __('Visits').' | '.config('app.name'))
@section('page-title', __('Property Visits'))
@section('dashboard-content')
    <div class="mx-auto max-w-5xl space-y-8">
        @foreach ([[__('My visit requests'), $requestedVisits, false], [__('Incoming requests'), $incomingVisits, true]] as [$heading, $visits, $ownerView])
            <section><div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-slate-950">{{ $heading }}</h2><span class="text-sm text-slate-500">{{ $visits->total() }}</span></div><div class="space-y-3">
                @forelse ($visits as $visit)
                    <article class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center"><div class="min-w-0"><a class="font-semibold text-slate-900 hover:text-indigo-600" href="{{ route('properties.show', $visit->listing) }}">{{ $visit->listing->title }}</a><p class="mt-1 text-sm text-slate-500">{{ $visit->scheduled_at->format('M j, Y · g:i A') }} · {{ $ownerView ? $visit->user->name : $visit->listing->owner->name }}</p>@if ($visit->note)<p class="mt-2 text-xs text-slate-500">{{ $visit->note }}</p>@endif</div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $visit->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : ($visit->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ __(ucfirst($visit->status)) }}</span>@if ($visit->status === 'pending') @if ($ownerView)<form action="{{ route('visits.update', $visit) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">{{ __('Confirm') }}</button></form>@endif<form action="{{ route('visits.update', $visit) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-red-600">{{ __('Cancel') }}</button></form>@endif</div></article>
                @empty <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">{{ __('No :type.', ['type' => strtolower($heading)]) }}</div> @endforelse
            </div>@if ($visits->hasPages())<div class="mt-4">{{ $visits->links() }}</div>@endif</section>
        @endforeach
    </div>
@endsection
