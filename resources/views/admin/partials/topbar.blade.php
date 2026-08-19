{{-- $unreadNotifications is supplied by the admin topbar view composer (AppServiceProvider). --}}
<header class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 dark:border-night-700/60 dark:bg-night-900">

    {{-- Sidebar toggle --}}
    <button id="sidebar-toggle" type="button"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-night-800"
            aria-label="Toggle sidebar">
        @include('admin.partials.icon', ['name' => 'bars-3', 'class' => 'w-5 h-5'])
    </button>

    {{-- Breadcrumb (injected by views) --}}
    <div class="min-w-0 flex-1">
        @isset($breadcrumb)
            {{ $breadcrumb }}
        @endisset
    </div>

    {{-- Right-side actions --}}
    <div class="flex items-center gap-1 sm:gap-3">

        {{-- Global search --}}
        <div class="relative hidden md:block"
             x-data="adminGlobalSearch('{{ route('admin.search') }}')"
             @keydown.window.prevent.cmd.k="$refs.input.focus(); $refs.input.select()"
             @keydown.window.prevent.ctrl.k="$refs.input.focus(); $refs.input.select()"
             @click.away="open = false">
            <div class="flex w-64 items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 transition-colors focus-within:border-brand/50 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand/10 lg:w-72 dark:border-night-700 dark:bg-night-800 dark:focus-within:bg-night-800">
                @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4 h-4 shrink-0 text-slate-400'])
                <input x-ref="input" x-model="query" type="search" placeholder="Search anything..."
                       autocomplete="off"
                       @input.debounce.250ms="search()"
                       @focus="if (results.length) open = true"
                       @keydown.escape="open = false; $refs.input.blur()"
                       @keydown.arrow-down.prevent="move(1)"
                       @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="go()"
                       class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-0 dark:text-slate-200 dark:placeholder-night-500 [&::-webkit-search-cancel-button]:appearance-none">            </div>

            {{-- Results dropdown --}}
            <div x-cloak x-show="open" x-transition.opacity.duration.150ms x-ref="list"
                 class="absolute left-0 right-0 top-full z-50 mt-2 max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white py-2 shadow-xl dark:border-night-700 dark:bg-night-800">
                <template x-if="loading">
                    <p class="px-4 py-3 text-sm text-slate-400">Searching…</p>
                </template>
                <template x-if="!loading && results.length === 0">
                    <p class="px-4 py-3 text-sm text-slate-400">No results found.</p>
                </template>
                <template x-for="(group, name) in grouped()" :key="name">
                    <div>
                        <p class="px-4 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-night-500" x-text="name"></p>
                        <template x-for="item in group" :key="item.url">
                            <a :href="link(item)"
                               :data-active="item._i === active"
                               @mouseenter="active = item._i"
                               class="flex items-center gap-3 px-4 py-2 data-[active=true]:bg-slate-50 dark:data-[active=true]:bg-night-700/60">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-brand dark:bg-brand/15">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-slate-700 dark:text-slate-200" x-html="highlight(item.label)"></span>
                                    <span class="block truncate text-xs text-slate-400" x-html="highlight(item.sub)"></span>
                                </span>
                            </a>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Dark mode toggle --}}
        <button id="dark-toggle" type="button"
                class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-night-800"
                title="Toggle dark mode">
            <span class="dark:hidden">@include('admin.partials.icon', ['name' => 'moon', 'class' => 'w-5 h-5'])</span>
            <span class="hidden dark:block">@include('admin.partials.icon', ['name' => 'sun', 'class' => 'w-5 h-5'])</span>
        </button>

        {{-- Notification bell — live count + dropdown, polled from the feed endpoint --}}
        @can('notifications.view')
            <div class="relative"
                 x-data="notificationBell({{ (int) $unreadNotifications }})"
                 data-feed-url="{{ route('admin.notifications.feed') }}"
                 data-mark-all-url="{{ route('admin.notifications.mark-all-read') }}"
                 data-admin-id="{{ auth()->id() }}"
                 @click.away="open = false"
                 @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu"
                        class="relative rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-night-800"
                        title="Notifications" aria-label="Notifications">
                    @include('admin.partials.icon', ['name' => 'bell', 'class' => 'w-5 h-5'])
                    <span x-cloak x-show="unread > 0"
                          class="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-night-900"
                          x-text="badge"></span>
                </button>

                <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                     class="absolute end-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-night-700 dark:bg-night-900 sm:w-96">
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-night-800">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifications</span>
                            <span x-cloak x-show="unread > 0"
                                  class="rounded-full bg-brand/10 px-2 py-0.5 text-[11px] font-semibold text-brand dark:bg-brand/20 dark:text-indigo-300"
                                  x-text="unread + ' unread'"></span>
                        </div>
                        <button type="button" @click="markAllRead()" x-show="unread > 0" x-cloak
                                class="text-xs font-medium text-slate-500 transition-colors hover:text-brand dark:text-slate-400">
                            Mark all read
                        </button>
                    </div>

                    {{-- List --}}
                    <div class="max-h-96 overflow-y-auto scrollbar-slim">
                        <template x-if="loaded && items.length === 0">
                            <div class="px-4 py-10 text-center text-sm text-slate-400">You're all caught up.</div>
                        </template>
                        <template x-if="!loaded">
                            <div class="px-4 py-10 text-center text-sm text-slate-400">Loading…</div>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <a :href="item.link || '{{ route('admin.notifications.index') }}'"
                               @click="markRead(item)"
                               class="flex items-start gap-3 border-b border-slate-50 px-4 py-3 transition-colors last:border-0 hover:bg-slate-50/70 dark:border-night-800/70 dark:hover:bg-night-800/50"
                               :class="!item.is_read ? 'bg-indigo-50/40 dark:bg-brand/5' : ''">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                      :class="item.is_read ? 'bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500' : item.icon_class">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4.5 w-4.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon_path" />
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span x-show="!item.is_read" class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="item.title"></p>
                                    </div>
                                    <p x-show="item.body" class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400" x-text="item.body"></p>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-night-500" x-text="item.time"></p>
                                </div>
                            </a>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <a href="{{ route('admin.notifications.index') }}"
                       class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-brand transition-colors hover:bg-slate-50 dark:border-night-800 dark:text-indigo-300 dark:hover:bg-night-800/50">
                        View all notifications
                    </a>
                </div>
            </div>
        @endcan

        {{-- Profile dropdown --}}
        @php
            $authUser = auth()->user();
            $authAvatarUrl = $authUser->avatar
                ? (str_starts_with($authUser->avatar, 'http') ? $authUser->avatar : asset('storage/'.$authUser->avatar))
                : null;
        @endphp
        <div class="relative" x-data="{ open: false }" @click.away="open = false">
            <button type="button" @click="open = !open"
                    class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-night-800">
                @if ($authAvatarUrl)
                    <img src="{{ $authAvatarUrl }}" alt="{{ $authUser->name }}" class="h-8 w-8 shrink-0 rounded-full object-cover">
                @else
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                        {{ strtoupper(substr($authUser->name, 0, 2)) }}
                    </span>
                @endif
                <span class="hidden max-w-28 truncate sm:block">{{ auth()->user()->name }}</span>
                @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-4 h-4 text-slate-400 hidden sm:block'])
            </button>

            <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                 class="absolute right-0 top-full z-50 mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-night-700 dark:bg-night-800">
                <div class="border-b border-slate-100 px-4 py-2.5 dark:border-night-700">
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                    @include('admin.partials.icon', ['name' => 'user-circle', 'class' => 'w-4 h-4 text-slate-400'])
                    My Profile
                </a>
                <a href="{{ route('admin.exports.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                    @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4 text-slate-400'])
                    Export History
                </a>
                <div class="mt-1 border-t border-slate-100 pt-1 dark:border-night-700">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                            @include('admin.partials.icon', ['name' => 'arrow-right-on-rect', 'class' => 'w-4 h-4'])
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</header>

<script>
    window.__adminSearchIcons = @json(\App\Services\AdminMenu::searchIconPaths());

    // Highlights occurrences of a term inside the page's <main> content and the
    // sidebar menu. Used live while typing in the global search and on ?hl= arrival.
    window.adminBodyHighlight = {
        roots() {
            return [document.querySelector('main'), document.querySelector('#sidebar nav')].filter(Boolean);
        },

        clear() {
            document.querySelectorAll('main span.admin-hl-wrap, #sidebar span.admin-hl-wrap').forEach((wrap) => {
                const parent = wrap.parentNode;
                parent.replaceChild(document.createTextNode(wrap.textContent), wrap);
                parent.normalize(); // merge adjacent text nodes so re-highlighting works
            });
        },

        apply(term) {
            this.clear();

            term = (term ?? '').trim();
            if (term.length < 2) return;

            const pattern = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            let count = 0;

            for (const root of this.roots()) {
                count = this.applyTo(root, pattern, count);
            }
        },

        applyTo(root, pattern, count) {
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: (node) => {
                    pattern.lastIndex = 0; // /g regexes are stateful across .test() calls
                    if (!node.nodeValue.trim() || !pattern.test(node.nodeValue)) return NodeFilter.FILTER_REJECT;
                    return node.parentElement?.closest('script, style, mark, input, textarea, select, svg, button, [contenteditable]')
                        ? NodeFilter.FILTER_REJECT
                        : NodeFilter.FILTER_ACCEPT;
                },
            });

            const nodes = [];
            while (walker.nextNode()) nodes.push(walker.currentNode);

            for (const node of nodes) {
                if (count >= 200) break;

                const text = node.nodeValue;
                // A single wrapper keeps the split text as ONE flex/grid item,
                // otherwise gap-* utilities push the pieces apart.
                const wrap = document.createElement('span');
                wrap.className = 'admin-hl-wrap';
                let last = 0, match;
                pattern.lastIndex = 0;

                while ((match = pattern.exec(text)) !== null) {
                    wrap.appendChild(document.createTextNode(text.slice(last, match.index)));
                    const mark = document.createElement('mark');
                    mark.className = 'admin-hl rounded-sm bg-amber-200/80 px-px text-inherit dark:bg-amber-400/30';
                    mark.textContent = match[0];
                    wrap.appendChild(mark);
                    last = match.index + match[0].length;
                    count++;
                }

                wrap.appendChild(document.createTextNode(text.slice(last)));
                node.parentNode.replaceChild(wrap, node);
            }

            return count;
        },
    };

    function adminGlobalSearch(endpoint) {
        return {
            query: '',
            open: false,
            loading: false,
            results: [],
            active: -1,
            seq: 0,

            async search() {
                const term = this.query.trim();

                // Live-highlight the term across the current page's body as you type.
                window.adminBodyHighlight.apply(term);

                if (term.length < 2) {
                    this.open = false;
                    this.results = [];
                    this.active = -1;
                    return;
                }

                this.loading = true;
                this.open = true;

                // Sequence guard: a slow earlier response must not overwrite
                // the results of the latest keystroke.
                const seq = ++this.seq;

                try {
                    const response = await fetch(endpoint + '?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (seq !== this.seq) return;
                    this.results = (data.results ?? []).map((item, i) => ({ ...item, _i: i }));
                    this.active = -1;
                } catch (e) {
                    if (seq === this.seq) this.results = [];
                } finally {
                    if (seq === this.seq) this.loading = false;
                }
            },

            grouped() {
                return this.results.reduce((groups, item) => {
                    (groups[item.group] ??= []).push(item);
                    return groups;
                }, {});
            },

            // Wrap every occurrence of the query in <mark>, escaping the rest.
            highlight(text) {
                text = String(text ?? '');
                const escape = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const term = this.query.trim();
                if (!term) return escape(text);

                const pattern = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                let out = '', last = 0, match;

                while ((match = pattern.exec(text)) !== null) {
                    out += escape(text.slice(last, match.index))
                        + '<mark class="rounded-sm bg-amber-200/80 px-px text-inherit dark:bg-amber-400/30">' + escape(match[0]) + '</mark>';
                    last = match.index + match[0].length;
                }

                return out + escape(text.slice(last));
            },

            move(delta) {
                if (!this.open || this.results.length === 0) return;
                this.active = (this.active + delta + this.results.length) % this.results.length;
                this.$nextTick(() => this.$refs.list?.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }));
            },

            // Carry the query to the destination page so it can highlight matches in its body.
            link(item) {
                const term = this.query.trim();
                if (!term) return item.url;

                return item.url + (item.url.includes('?') ? '&' : '?') + 'hl=' + encodeURIComponent(term);
            },

            go() {
                const item = this.results[this.active] ?? this.results[0];
                if (item) window.location.href = this.link(item);
            },

            iconPath(name) {
                const paths = window.__adminSearchIcons ?? {};

                return paths[name] ?? paths['magnifying-glass'] ?? '';
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Highlight the searched term (?hl=...) in the page body after arriving
        // from a global search result, then scroll to the first match.
        const arrivalTerm = new URLSearchParams(window.location.search).get('hl');
        if (arrivalTerm) {
            window.adminBodyHighlight.apply(arrivalTerm);
            document.querySelector('mark.admin-hl')?.scrollIntoView({ block: 'center' });
        }

        // Sidebar toggle: slide-in on mobile, collapse on desktop
        document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            if (window.innerWidth >= 1024) {
                sidebar?.classList.toggle('lg:-ml-64');
            } else {
                sidebar?.classList.toggle('-translate-x-full');
                backdrop?.classList.toggle('opacity-0');
                backdrop?.classList.toggle('pointer-events-none');
            }
        });

        // Dark mode — suppress transitions during the switch so the whole page flips
        // instantly instead of animating hundreds of colour transitions.
        document.getElementById('dark-toggle')?.addEventListener('click', function () {
            const root = document.documentElement;
            root.classList.add('theme-switching');
            const isDark = root.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: isDark } }));
            requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove('theme-switching')));
        });
    });
</script>
