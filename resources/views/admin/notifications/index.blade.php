<x-admin-layout title="Notifications">
    <x-admin-page-header title="Notifications"
        :breadcrumbs="[['label' => 'Notifications']]"
        :description="$unreadCount > 0 ? $unreadCount.' unread notifications' : 'No unread notifications'">
        <x-slot name="actions">
            <a href="{{ route('admin.notifications.preferences') }}"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'bell', 'class' => 'w-4 h-4 text-slate-400'])
                Preferences
            </a>

            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                        @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                        Mark All Read
                    </button>
                </form>
            @endif
        </x-slot>
    </x-admin-page-header>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.notifications.index') }}"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="unread_only" value="1" @checked(request()->boolean('unread_only')) onchange="this.form.submit()"
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Unread only
            </label>

            <div class="ml-auto flex items-center gap-2">
                @if(request()->hasAny(['unread_only']))
                    <a href="{{ route('admin.notifications.index') }}"
                       class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                        Clear
                    </a>
                @endif
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'funnel', 'class' => 'w-4 h-4 text-slate-400'])
                    Apply
                </button>
            </div>
        </div>
    </form>

    {{-- Feed --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900">
        <div class="divide-y divide-slate-100 dark:divide-night-800">
            @forelse($notifications as $notification)
                @php
                    $meta = \App\Models\AdminNotification::metaFor($notification->type);
                    $iconStyle = $notification->is_read
                        ? 'bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500'
                        : $meta['style'];
                @endphp
                <div class="flex flex-col items-start gap-3 px-5 py-3.5 transition-colors hover:bg-slate-50/60 sm:flex-row sm:items-center dark:hover:bg-night-800/40 {{ $notification->is_read ? '' : 'bg-indigo-50/40 dark:bg-brand/5' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconStyle }}">
                        @include('admin.partials.icon', ['name' => $meta['icon'], 'class' => 'w-5 h-5'])
                    </span>

                    <div class="min-w-0 w-full flex-1 sm:w-auto">
                        <div class="flex items-center gap-2">
                            @unless($notification->is_read)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-brand"></span>
                            @endunless
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $notification->title }}</p>
                        </div>
                        @if($notification->body)
                            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $notification->body }}</p>
                        @endif
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:flex-nowrap sm:shrink-0">
                        @if($notification->link)
                            <a href="{{ $notification->link }}"
                               class="text-xs font-medium text-brand transition-colors hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">View</a>
                        @endif
                        @unless($notification->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Mark Read</button>
                            </form>
                        @endunless
                        <span class="shrink-0 text-xs text-slate-400 dark:text-night-500">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center text-sm text-slate-400">No notifications found.</div>
            @endforelse
        </div>

        {{ $notifications->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</x-admin-layout>
