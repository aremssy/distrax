@extends('website.layouts.master')

@section('content')
    <div class="bg-slate-50 py-4 sm:py-6" data-purpose="dashboard-shell">
        <div class="max-w-7xl mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[240px_1fr] lg:gap-8">
                {{-- Sidebar --}}
                <aside class="lg:sticky lg:top-24">
                    <a href="{{ route('dashboard') }}" class="mb-5 hidden items-center gap-3 lg:flex">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/30">
                            <i class="h-5 w-5" data-lucide="house" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-bold text-slate-950">{{ setting('site_name', config('app.name')) }}</span>
                            <span class="block text-xs text-slate-500">{{ ($headerRoleName ?? null) ? $headerRoleName.' Dashboard' : 'Dashboard' }}</span>
                        </span>
                    </a>

                    <nav class="-mx-4 flex snap-x gap-1 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                        @php
                            // Technicians get their own two entries; everyone else sees a
                            // single prompt to apply.
                            $technicianNav = auth()->user()?->technicianProfile()->exists()
                                ? [
                                    ['technician.profile.edit', 'wrench', 'Technician Profile'],
                                    ['technician.bookings.index', 'clipboard-list', 'My Jobs'],
                                    ['technician.maintenance-requests.index', 'hammer', 'Maintenance Jobs'],
                                ]
                                : [['technician.apply', 'wrench', 'Become a Technician']];

                            $navItems = [
                                ['dashboard', 'layout-dashboard', 'Overview'],
                                ['dashboard.wishlist', 'heart', 'Favorites & Searches'],
                                ['offers.index', 'hand-coins', 'My Offers'],
                                ['inspections.index', 'scan-search', 'Inspections'],
                                ['dashboard.compare', 'columns-3', 'Compare'],
                                ['dashboard.messages', 'message-square', 'Messages'],
                                ['dashboard.notifications', 'bell', 'Notifications'],
                                ['dashboard.wallet', 'wallet', 'Wallet & Plan'],
                                ['escrow-invest.index', 'shield-check', 'Escrow & Invest'],
                                ['dashboard.reports', 'flag', 'Reports & Disputes'],
                                ['owner.listings.index', 'building-2', 'My Listings'],
                                ...(auth()->user()?->institutionalAccount ? [['institutional.index', 'landmark', 'Institutional']] : []),
                                ['owner.leads.index', 'trending-up', 'Leads & Analytics'],
                                ['owner.rent-management.index', 'receipt-text', 'Rent Management'],
                                ['dashboard.maintenance.index', 'hammer', 'Maintenance Requests'],
                                ['owner.agency.index', 'users', 'My Agency'],
                                ...$technicianNav,
                                ['dashboard.profile', 'user', 'Profile'],
                                ['dashboard.activity', 'activity', 'Activity'],
                                ['dashboard.settings', 'settings', 'Settings'],
                            ];
                            $navBadges = [
                                'dashboard.messages' => $headerUnreadMessageCount ?? 0,
                                'dashboard.notifications' => $headerUnreadNotificationCount ?? 0,
                            ];
                        @endphp
                        @foreach ($navItems as [$routeName, $icon, $label])
                            @php
                                // Resourceful items ('*.index') highlight for their whole group
                                // (create/edit/show); nested items match their own children; the
                                // bare 'dashboard' base matches only itself so it never lights up
                                // on sibling routes like dashboard.activity.
                                $wildcard = \Illuminate\Support\Str::endsWith($routeName, '.index')
                                    ? \Illuminate\Support\Str::beforeLast($routeName, '.index').'.*'
                                    : (\Illuminate\Support\Str::contains($routeName, '.') ? $routeName.'.*' : null);
                                $isCurrent = request()->routeIs($routeName) || ($wildcard && request()->routeIs($wildcard));
                            @endphp
                            <a href="{{ route($routeName) }}" @if ($isCurrent) aria-current="page" @endif
                                class="flex min-h-11 shrink-0 snap-start items-center gap-3 whitespace-nowrap rounded-lg px-3.5 py-2.5 text-sm font-medium transition lg:shrink {{ $isCurrent ? 'bg-indigo-50 font-semibold text-indigo-600' : 'text-slate-600 hover:bg-white hover:text-slate-950' }}">
                                <i class="w-4 h-4 shrink-0" data-lucide="{{ $icon }}"></i>
                                {{ $label }}
                                @if (($navBadges[$routeName] ?? 0) > 0)
                                    <span
                                        class="ml-auto w-5 h-5 flex items-center justify-center text-[10px] font-bold rounded-full {{ $isCurrent ? 'bg-indigo-600 text-white' : 'bg-slate-950 text-white' }}">{{ $navBadges[$routeName] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-6 hidden rounded-2xl border border-slate-200 bg-white p-5 text-center lg:block">
                        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                            <i class="h-5 w-5" data-lucide="headphones" aria-hidden="true"></i>
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-950">Need Help?</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">We're here to help you.</p>
                        <a href="{{ route('contact') }}"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50">
                            Contact Support
                        </a>
                    </div>
                </aside>

                {{-- Main --}}
                <div class="min-w-0">
                    <div class="mb-6 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-950">@yield('page-title', 'Dashboard')</h1>
                            @hasSection('page-subtitle')
                                <p class="mt-1 text-sm text-slate-600">@yield('page-subtitle')</p>
                            @endif
                        </div>

                        <div class="flex w-full shrink-0 items-center justify-end gap-2 sm:w-auto">
                            <div class="relative">
                                <button type="button" data-dropdown-toggle="notifications-dropdown"
                                    aria-label="Notifications"
                                    class="relative w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-white border border-transparent hover:border-slate-200">
                                    <i class="w-5 h-5" data-lucide="bell"></i>
                                    @if (($headerUnreadNotificationCount ?? 0) > 0)
                                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-slate-50">
                                            {{ min($headerUnreadNotificationCount, 99) }}
                                        </span>
                                    @endif
                                </button>
                                <div id="notifications-dropdown"
                                    class="absolute right-0 z-50 mt-2 hidden w-[min(20rem,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white py-2 shadow-lg">
                                    <div class="px-4 py-2 flex items-center justify-between">
                                        <span class="text-sm font-semibold text-slate-950">Notifications</span>
                                        <a href="{{ route('dashboard.notifications') }}"
                                            class="text-xs text-slate-500 hover:text-slate-950">View all</a>
                                    </div>
                                    <div class="border-t border-slate-100">
                                        @forelse (($headerNotifications ?? []) as $notification)
                                            <form action="{{ route('dashboard.notifications.read', $notification) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-slate-50 {{ $notification->is_read ? '' : 'bg-indigo-50/40' }}">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                                        <i class="w-4 h-4 text-slate-500" data-lucide="bell"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="text-sm text-slate-700 leading-snug">{{ $notification->title }}</p>
                                                        <p class="text-xs text-slate-500 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                                    </div>
                                                </button>
                                            </form>
                                        @empty
                                            <p class="px-4 py-6 text-center text-sm text-slate-500">No notifications yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <button type="button" data-dropdown-toggle="account-dropdown"
                                    class="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-lg hover:bg-white border border-transparent hover:border-slate-200">
                                    <img src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : asset('assets/images/website/agent/agent-5.webp') }}"
                                        class="w-9 h-9 rounded-full object-cover" alt="{{ auth()->user()->name }}">
                                    <span class="hidden min-w-0 text-start sm:block">
                                        <span class="block truncate text-sm font-semibold text-slate-950">{{ auth()->user()->name }}</span>
                                        @if ($headerRoleName ?? null)
                                            <span class="block truncate text-xs text-slate-500">{{ $headerRoleName }}</span>
                                        @endif
                                    </span>
                                    <i class="w-4 h-4 text-slate-500" data-lucide="chevron-down"></i>
                                </button>
                                <div id="account-dropdown"
                                    class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl border border-slate-200 shadow-lg py-1.5 z-50">
                                    <a href="{{ route('dashboard.profile') }}"
                                        class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                        <i class="w-4 h-4" data-lucide="user"></i> Profile
                                    </a>
                                    <a href="{{ route('dashboard.settings') }}"
                                        class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                        <i class="w-4 h-4" data-lucide="settings"></i> Settings
                                    </a>
                                    <div class="border-t border-slate-100 my-1.5"></div>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="w-4 h-4" data-lucide="log-out"></i> Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @yield('dashboard-content')
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script data-purpose="dashboard-dropdowns">
            document.querySelectorAll('[data-dropdown-toggle]').forEach((trigger) => {
                const panel = document.getElementById(trigger.dataset.dropdownToggle);
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isOpen = !panel.classList.contains('hidden');
                    document.querySelectorAll('[id$="-dropdown"]').forEach((p) => p.classList.add('hidden'));
                    panel.classList.toggle('hidden', isOpen);
                });
            });
            document.addEventListener('click', () => {
                document.querySelectorAll('[id$="-dropdown"]').forEach((p) => p.classList.add('hidden'));
            });
        </script>
    @endpush
@endsection
