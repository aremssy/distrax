@extends('website.layouts.dashboard')
@section('title', __('Messages').' | '.config('app.name'))
@section('page-title', __('Messages'))
@section('page-subtitle', __('Communicate with clients and manage your conversations.'))
@section('dashboard-content')
    @php
        $userId = auth()->id();
        $avatarPalette = ['bg-indigo-600', 'bg-violet-600', 'bg-blue-600', 'bg-sky-600', 'bg-emerald-600', 'bg-rose-500', 'bg-amber-600'];
        $initials = function (?string $name) {
            $words = preg_split('/\s+/', trim((string) ($name ?: 'User')));
            $first = \Illuminate\Support\Str::substr($words[0] ?? 'U', 0, 1);
            $second = \Illuminate\Support\Str::substr($words[1] ?? '', 0, 1);

            return \Illuminate\Support\Str::upper($first.$second);
        };
        $avatarColor = fn (?string $name) => $avatarPalette[array_sum(array_map('ord', str_split((string) ($name ?: 'U')))) % count($avatarPalette)];
        $unreadThreads = $conversations->where('unread_count', '>', 0)->count();
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="flex h-[calc(100dvh-11rem)] max-h-[780px] min-h-[520px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {{-- Conversations panel --}}
            <aside x-data="{ q: '', unreadOnly: false, matches(t, u) { return (this.q === '' || t.includes(this.q.toLowerCase())) && (!this.unreadOnly || u); } }"
                class="flex w-full flex-col border-r border-slate-200 sm:w-80 sm:shrink-0 {{ $activeConversation ? 'hidden sm:flex' : 'flex' }}">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="text-base font-bold text-slate-900">{{ __('Conversations') }}</h2>
                    <div class="mt-3 flex items-center gap-2">
                        <div class="relative flex-1">
                            <i class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" data-lucide="search"></i>
                            <input type="search" x-model="q" placeholder="{{ __('Search conversations...') }}" aria-label="{{ __('Search conversations') }}"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button type="button" @click="unreadOnly = !unreadOnly" aria-label="{{ __('Filter unread') }}"
                            :class="unreadOnly ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-slate-200 text-slate-500 hover:bg-slate-50'"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition">
                            <i class="h-4 w-4" data-lucide="sliders-horizontal"></i>
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto scrollbar-slim">
                    @forelse ($conversations as $item)
                        @php($other = $item->starter_id === $userId ? $item->recipient : $item->starter)
                        @php($preview = $item->latestMessage?->body ?: ($item->latestMessage?->attachments->isNotEmpty() ? __('Attachment') : __('No messages yet')))
                        <a href="{{ route('dashboard.messages.show', $item) }}"
                            x-show="matches(@js(\Illuminate\Support\Str::lower(($other?->name ?? 'User').' '.($item->listing?->title ?? '').' '.$preview)), {{ $item->unread_count ? 'true' : 'false' }})"
                            class="flex items-start gap-3 border-b border-slate-100 px-4 py-3.5 transition hover:bg-slate-50 {{ $activeConversation?->is($item) ? 'bg-indigo-50/70' : '' }}">
                            @if ($other?->avatar)
                                <img src="{{ asset('storage/'.$other->avatar) }}" alt="{{ $other->name }}" class="h-11 w-11 shrink-0 rounded-full object-cover">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $avatarColor($other?->name) }}">{{ $initials($other?->name) }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-bold text-slate-900">{{ $other?->name ?? __('User') }}</span>
                                    <time class="shrink-0 text-[11px] text-slate-400">{{ $item->last_message_at?->diffForHumans(['short' => true]) }}</time>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item->listing?->title ?? __('General conversation') }}</p>
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="truncate text-sm {{ $item->unread_count ? 'font-semibold text-slate-700' : 'text-slate-500' }}">{{ $preview }}</p>
                                    @if ($item->unread_count)
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-600" aria-label="{{ __(':count unread', ['count' => $item->unread_count]) }}"></span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">{{ __('No conversations yet. Contact an owner from a property page.') }}</div>
                    @endforelse

                    @if ($conversations->hasPages())
                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 text-sm">
                            @if ($conversations->onFirstPage())
                                <span class="text-slate-300">{{ __('Previous') }}</span>
                            @else
                                <a href="{{ $conversations->previousPageUrl() }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ __('Previous') }}</a>
                            @endif
                            <span class="text-xs text-slate-400">{{ __('Page :current of :last', ['current' => $conversations->currentPage(), 'last' => $conversations->lastPage()]) }}</span>
                            @if ($conversations->hasMorePages())
                                <a href="{{ $conversations->nextPageUrl() }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ __('Next') }}</a>
                            @else
                                <span class="text-slate-300">{{ __('Next') }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="border-t border-slate-100 p-4">
                    <span class="flex items-center gap-2 text-sm font-medium text-indigo-600">
                        <i class="h-4 w-4" data-lucide="circle-plus"></i>
                        {{ __(':count Unread :label', ['count' => $unreadThreads, 'label' => \Illuminate\Support\Str::plural('conversation', $unreadThreads)]) }}
                    </span>
                </div>
            </aside>

            {{-- Chat pane --}}
            <section class="{{ $activeConversation ? 'flex' : 'hidden sm:flex' }} min-w-0 flex-1 flex-col">
                @if ($activeConversation)
                    @php($other = $activeConversation->starter_id === $userId ? $activeConversation->recipient : $activeConversation->starter)
                    @php($canCall = $other?->phone && in_array($other->phone_visibility, ['everyone', 'registered'], true))
                    <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <a class="shrink-0 text-slate-500 hover:text-indigo-600 sm:hidden" href="{{ route('dashboard.messages') }}" aria-label="{{ __('Back to conversations') }}">
                                <i class="h-5 w-5" data-lucide="arrow-left"></i>
                            </a>
                            @if ($other?->avatar)
                                <img src="{{ asset('storage/'.$other->avatar) }}" alt="{{ $other->name }}" class="h-11 w-11 shrink-0 rounded-full object-cover">
                            @else
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $avatarColor($other?->name) }}">{{ $initials($other?->name) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-900">{{ $other?->name ?? __('User') }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $activeConversation->listing?->title ?? __('General conversation') }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($canCall)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $other->phone) }}" aria-label="{{ __('Call :name', ['name' => $other->name]) }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-indigo-600 hover:text-white">
                                    <i class="h-4 w-4" data-lucide="phone"></i>
                                </a>
                            @endif
                            @if ($activeConversation->listing)
                                <a href="{{ route('properties.show', $activeConversation->listing) }}" aria-label="{{ __('View property details') }}"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-indigo-600 hover:text-white">
                                    <i class="h-4 w-4" data-lucide="info"></i>
                                </a>
                            @endif
                            @if ($other)
                                @if ($isOtherBlocked)
                                    <form action="{{ route('account.blocks.destroy', $existingBlock) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="{{ __('Unblock :name', ['name' => $other->name]) }}"
                                            class="flex h-9 items-center gap-1.5 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-600 transition hover:bg-indigo-600 hover:text-white">
                                            <i class="h-4 w-4" data-lucide="user-check"></i>{{ __('Unblock') }}
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('account.blocks.store', $other) }}" method="POST"
                                        data-confirm="{{ __('Block :name? They won\'t be able to message you anymore.', ['name' => addslashes($other->name)]) }}">
                                        @csrf
                                        <button type="submit" aria-label="{{ __('Block :name', ['name' => $other->name]) }}"
                                            class="flex h-9 items-center gap-1.5 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-600 transition hover:bg-red-600 hover:text-white">
                                            <i class="h-4 w-4" data-lucide="user-x"></i>{{ __('Block') }}
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </header>

                    <div id="chat-thread" data-conversation-id="{{ $activeConversation->id }}" data-user-id="{{ $userId }}"
                        x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                        class="min-h-0 flex-1 space-y-1 overflow-y-auto scrollbar-slim bg-white p-4 sm:p-6">
                        @php($lastDate = null)
                        @forelse ($messages as $message)
                            @php($label = $message->created_at->isToday() ? __('Today') : ($message->created_at->isYesterday() ? __('Yesterday') : $message->created_at->format('F j, Y')))
                            @if ($label !== $lastDate)
                                <div class="flex justify-center py-3">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">{{ $label }}</span>
                                </div>
                                @php($lastDate = $label)
                            @endif
                            @php($mine = $message->sender_id === $userId)
                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[78%]">
                                    <div class="rounded-2xl px-4 py-2.5 {{ $mine ? 'rounded-br-md bg-indigo-600 text-white' : 'rounded-bl-md bg-slate-100 text-slate-700' }}">
                                        @if ($message->body !== '')
                                            <p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>
                                        @endif
                                        @foreach ($message->attachments as $attachment)
                                            @if ($attachment->isImage())
                                                <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="mt-2 block">
                                                    <img src="{{ $attachment->url }}" alt="{{ $attachment->original_name }}" class="h-auto max-h-52 max-w-full rounded-lg object-cover">
                                                </a>
                                            @else
                                                <a href="{{ $attachment->url }}" target="_blank" rel="noopener" download
                                                    class="mt-2 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition {{ $mine ? 'bg-white/15 text-white hover:bg-white/25' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                                                    <i class="h-4 w-4 shrink-0" data-lucide="file"></i>
                                                    <span class="truncate">{{ $attachment->original_name }}</span>
                                                </a>
                                            @endif
                                        @endforeach
                                        <div class="mt-1 flex items-center justify-end gap-1 {{ $mine ? 'text-white/70' : 'text-slate-400' }}">
                                            <time class="text-[10px]">{{ $message->created_at->format('g:i A') }}</time>
                                            @if ($mine)
                                                <i class="h-3.5 w-3.5" data-lucide="{{ $message->read_at ? 'check-check' : 'check' }}"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                                <i class="h-7 w-7 text-slate-400" data-lucide="messages-square"></i>
                                <p class="text-sm text-slate-500">{{ __('No messages yet. Say hello 👋') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <form x-data="{ message: '', emojiOpen: false, files: [], emojis: ['😀','😅','😂','😍','👍','🙏','🎉','🔥','✅','❤️','🏠','📎','😊','🤝','👀'], addEmoji(e) { this.message += e; this.emojiOpen = false; }, onFiles(ev) { this.files = Array.from(ev.target.files).map(f => f.name); } }"
                        class="border-t border-slate-100 p-3 sm:p-4" action="{{ route('conversations.messages.store', $activeConversation) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <template x-if="files.length">
                            <div class="mb-2 flex flex-wrap gap-2">
                                <template x-for="(name, i) in files" :key="i">
                                    <span class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600">
                                        <i class="h-3.5 w-3.5" data-lucide="paperclip"></i><span x-text="name"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                        <div class="flex items-center gap-2">
                            <label class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-indigo-600" aria-label="{{ __('Attach files') }}">
                                <i class="h-5 w-5" data-lucide="paperclip"></i>
                                <input type="file" name="attachments[]" multiple class="hidden" @change="onFiles($event)"
                                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            </label>
                            <div class="relative flex flex-1 items-center">
                                <label class="sr-only" for="message">{{ __('Message') }}</label>
                                <input id="message" name="message" x-model="message" maxlength="2000" autocomplete="off" placeholder="{{ __('Type a message...') }}"
                                    class="w-full rounded-lg border border-slate-200 py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <div class="absolute right-2" @click.outside="emojiOpen = false">
                                    <button type="button" @click="emojiOpen = !emojiOpen" aria-label="{{ __('Add emoji') }}" class="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:text-indigo-600">
                                        <i class="h-5 w-5" data-lucide="smile"></i>
                                    </button>
                                    <div x-show="emojiOpen" x-cloak x-transition.opacity
                                        class="absolute bottom-full right-0 z-10 mb-2 grid w-52 grid-cols-6 gap-1 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                                        <template x-for="e in emojis" :key="e">
                                            <button type="button" @click="addEmoji(e)" class="rounded-md p-1 text-lg hover:bg-slate-100" x-text="e"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" :disabled="!message.trim() && !files.length"
                                class="flex h-11 shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <i class="h-4 w-4" data-lucide="send"></i>
                                <span class="hidden sm:inline">{{ __('Send') }}</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="m-auto flex flex-col items-center gap-2 text-center">
                        <i class="h-8 w-8 text-slate-400" data-lucide="messages-square"></i>
                        <p class="text-sm text-slate-500">{{ __('Select a conversation to read messages.') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @if ($activeConversation)
        @push('scripts')
            <script>
            (function () {
                const thread = document.getElementById('chat-thread');
                if (!thread || !window.Echo) return;

                const conversationId = thread.dataset.conversationId;
                const currentUserId = Number(thread.dataset.userId);

                try {
                    window.Echo.private(`conversation.${conversationId}`).listen('.message.sent', (event) => {
                        // Own messages already appear via the normal form submit/reload — avoid duplicating them.
                        if (event.sender_id === currentUserId) return;

                        const mine = false;
                        const bubble = document.createElement('div');
                        bubble.className = 'flex justify-start';
                        bubble.innerHTML = `
                            <div class="max-w-[78%]">
                                <div class="rounded-2xl rounded-bl-md bg-slate-100 px-4 py-2.5 text-slate-700">
                                    <p class="whitespace-pre-wrap break-words text-sm"></p>
                                </div>
                            </div>`;
                        bubble.querySelector('p').textContent = event.body;
                        thread.appendChild(bubble);
                        thread.scrollTop = thread.scrollHeight;
                    });
                } catch (e) {
                    // Echo present but misconfigured — the page still works via normal form submits.
                }
            })();
            </script>
        @endpush
    @endif
@endsection
