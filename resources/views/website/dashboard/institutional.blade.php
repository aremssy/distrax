@extends('website.layouts.dashboard')

@section('title', __('Institutional Portfolio').' | '.config('app.name'))
@section('page-title', __('Institutional Portfolio'))

@section('dashboard-content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif

        @if (! $account)
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ __('No institutional account yet') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Set your profile as an institutional buyer to unlock the portfolio dashboard.') }}</p>
            </section>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">{{ $activeCount }}</p>
                    <p class="text-sm text-slate-500">{{ __('Active listings') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">{{ $pendingCount }}</p>
                    <p class="text-sm text-slate-500">{{ __('Awaiting verification') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-2xl font-bold text-slate-900">{{ $batches->count() }}</p>
                    <p class="text-sm text-slate-500">{{ __('Upload batches') }}</p>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-slate-900">{{ __('Bulk upload') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('CSV columns: title, type, zone_slug, price, description, bedrooms, bathrooms, address. Rows are validated and enter the normal verification flow before going live.') }}</p>
                <form method="POST" action="{{ route('institutional.upload') }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <input type="file" name="file" accept=".csv,.txt" required
                        class="flex-1 rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">{{ __('Import portfolio') }}</button>
                </form>
                @error('file')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('Upload batches') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">File</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Created</th>
                                <th class="px-5 py-3 text-right">Rows</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($batches as $batch)
                                <tr>
                                    <td class="px-5 py-3.5 text-slate-700">{{ $batch->original_filename }}</td>
                                    <td class="px-3 py-3.5">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                            @if ($batch->status === 'complete') bg-emerald-100 text-emerald-700
                                            @elseif ($batch->status === 'partial') bg-amber-100 text-amber-700
                                            @else bg-slate-100 text-slate-600 @endif">{{ ucfirst($batch->status) }}</span>
                                    </td>
                                    <td class="px-3 py-3.5 text-slate-500">{{ $batch->created_at->diffForHumans() }}</td>
                                    <td class="px-5 py-3.5 text-right text-slate-500">{{ $batch->created_count }}/{{ $batch->total_rows }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-400">No uploads yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('Portfolio listings') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Title</th>
                                <th class="px-3 py-3">Zone</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($listings as $listing)
                                <tr>
                                    <td class="px-5 py-3.5 font-semibold text-slate-800">
                                        <a href="{{ route('properties.show', $listing->slug) }}" class="hover:text-indigo-600">{{ $listing->title }}</a>
                                    </td>
                                    <td class="px-3 py-3.5 text-slate-500">{{ $listing->zone?->name ?? '—' }}</td>
                                    <td class="px-3 py-3.5">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                            @if ($listing->status === 'active') bg-emerald-100 text-emerald-700
                                            @else bg-slate-100 text-slate-600 @endif">{{ ucfirst($listing->status) }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-slate-800">{{ money($listing->price, $listing->currency_code) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-400">No listings yet. Upload a portfolio CSV.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
