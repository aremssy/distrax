@extends('website.layouts.dashboard')
@section('title', __('Notifications').' | '.config('app.name'))
@section('page-title', __('Notifications'))
@section('dashboard-content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex items-center justify-end">
            <form action="{{ route('dashboard.notifications.read-all') }}" method="POST">
                @csrf
                <button class="text-sm font-semibold text-indigo-600" type="submit">{{ __('Mark all as read') }}</button>
            </form>
        </div>
        <div class="space-y-3">
            @forelse ($notifications as $notification)
                <article class="flex flex-col items-stretch justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-start sm:p-5 {{ $notification->is_read ? '' : 'ring-1 ring-indigo-200' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100"><i class="h-4 w-4 text-slate-500" data-lucide="bell"></i></div>
                        <div>
                            <p class="font-semibold text-slate-950">{{ $notification->title }}</p>
                            @if ($notification->body)<p class="mt-1 text-sm text-slate-500">{{ $notification->body }}</p>@endif
                            <p class="mt-2 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @unless ($notification->is_read)
                        <form class="self-end sm:self-auto" action="{{ route('dashboard.notifications.read', $notification) }}" method="POST">
                            @csrf
                            <button class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700" type="submit">{{ __('Mark read') }}</button>
                        </form>
                    @endunless
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">{{ __('No notifications yet.') }}</div>
            @endforelse
        </div>
        <div>{{ $notifications->links() }}</div>
    </div>
@endsection
