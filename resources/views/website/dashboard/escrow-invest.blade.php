@extends('website.layouts.dashboard')

@section('title', __('Escrow & Invest').' | '.config('app.name'))
@section('page-title', __('Escrow & Invest'))

@section('dashboard-content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold text-slate-900">{{ __('Distrax Escrow') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">
                {{ __('"Coming soon via licensed partner". Distrax Escrow will let you hold transaction funds with a licensed escrow partner. It is not available yet and is shown for informational purposes only — no payments are processed here.') }}
            </p>
            <span class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ __('Coming soon') }}</span>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-slate-900">{{ __('Distrax Invest — Pooled opportunities') }}</h2>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-indigo-600">{{ __('Informational only') }}</span>
            </div>
            <p class="mt-1 text-sm leading-6 text-slate-600">
                {{ __('A read-only list of pooled investment opportunities. Selecting one performs no transaction. Investing will require regulatory sign-off and a separately licensed product.') }}
            </p>

            @if ($pooledOpportunities->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                    {{ __('No pooled opportunities are currently listed.') }}
                </div>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($pooledOpportunities as $opp)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $opp['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $opp['note'] }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ __('View only') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
