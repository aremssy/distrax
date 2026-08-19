<x-admin-layout title="Dashboard">
    @push('head')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    @endpush

    @php
        $currencySymbol = trim(preg_replace('/[0-9.,\s]/u', '', money(0))) ?: '$';
        $rangeLabel = now()->subDays($days - 1)->format('M j').' – '.now()->format('M j, Y');

        $statCards = [
            ['key' => 'total_users',          'label' => 'Total Users',          'icon' => 'users',           'style' => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400'],
            ['key' => 'active_listings',      'label' => 'Active Listings',      'icon' => 'briefcase',       'style' => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400'],
            ['key' => 'today_new_listings',   'label' => "Today's New Posts",    'icon' => 'calendar-days',   'style' => 'bg-blue-50 text-blue-500 dark:bg-blue-500/15 dark:text-blue-400'],
            ['key' => 'revenue_month',        'label' => 'Month Revenue',        'icon' => 'currency-dollar', 'style' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400', 'money' => true],
            ['key' => 'pending_reviews',      'label' => 'Pending Reviews',      'icon' => 'shield-check',    'style' => 'bg-violet-50 text-violet-500 dark:bg-violet-500/15 dark:text-violet-400'],
            ['key' => 'unread_notifications', 'label' => 'Unread Notifications', 'icon' => 'bell',            'style' => 'bg-orange-50 text-orange-400 dark:bg-orange-500/15 dark:text-orange-400'],
        ];

        $zoneColors = ['#6366f1', '#10b981', '#3b82f6', '#14b8a6', '#f59e0b', '#ef4444', '#ec4899', '#94a3b8'];

        $activityStyles = [
            'user'    => ['icon' => 'user-plus',       'style' => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400'],
            'listing' => ['icon' => 'home-modern',     'style' => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400'],
            'booking' => ['icon' => 'calendar-days',   'style' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400'],
            'payment' => ['icon' => 'currency-dollar', 'style' => 'bg-teal-50 text-teal-500 dark:bg-teal-500/15 dark:text-teal-400'],
        ];

        $statusBadges = [
            'active'   => ['label' => 'Published', 'style' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
            'pending'  => ['label' => 'Pending',   'style' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400'],
            'rented'   => ['label' => 'Rented',    'style' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400'],
            'rejected' => ['label' => 'Rejected',  'style' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400'],
        ];
    @endphp

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Welcome back, {{ auth()->user()->name }}! Here's what's happening today.</p>
        </div>

        {{-- Date range picker --}}
        <div x-data="{ open: false }" @click.away="open = false" class="relative">
            <button type="button" @click="open = !open"
                    class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'calendar-days', 'class' => 'w-4 h-4 text-slate-400'])
                {{ $rangeLabel }}
                @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-4 h-4 text-slate-400'])
            </button>
            <div x-cloak x-show="open" x-transition.opacity.duration.150ms
                 class="absolute right-0 top-full z-30 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-night-700 dark:bg-night-800">
                @foreach ([7, 30, 90] as $option)
                    <a href="{{ route('admin.dashboard', ['range' => $option]) }}"
                       class="flex items-center justify-between px-4 py-2 text-sm {{ $days === $option ? 'font-semibold text-brand' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60' }}">
                        Last {{ $option }} Days
                        @if ($days === $option)
                            @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($statCards as $card)
            @if (array_key_exists($card['key'], $kpis))
                @php $trend = $kpiTrends[$card['key']] ?? 0.0; @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-night-700 dark:bg-night-900">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $card['style'] }}">
                            @include('admin.partials.icon', ['name' => $card['icon'], 'class' => 'w-5.5 h-5.5'])
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-0.5 truncate text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ ($card['money'] ?? false) ? money($kpis[$card['key']]) : number_format($kpis[$card['key']]) }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3.5 flex items-center gap-1.5 text-xs">
                        @if ($trend > 0)
                            @include('admin.partials.icon', ['name' => 'arrow-trending-up', 'class' => 'w-3.5 h-3.5 text-emerald-500'])
                            <span class="font-semibold text-emerald-500">{{ $trend }}%</span>
                        @elseif ($trend < 0)
                            @include('admin.partials.icon', ['name' => 'arrow-trending-down', 'class' => 'w-3.5 h-3.5 text-rose-500'])
                            <span class="font-semibold text-rose-500">{{ abs($trend) }}%</span>
                        @else
                            <span class="font-semibold text-slate-400">— 0%</span>
                        @endif
                        <span class="text-slate-400 dark:text-night-500">vs last {{ $days }} days</span>
                    </p>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Charts: user growth + revenue --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">User Growth ({{ $days }} days)</h2>
                <span class="relative">
                    <select onchange="window.location.href = '{{ route('admin.dashboard') }}?range=' + this.value"
                            class="cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs font-medium text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                        @foreach ([7, 30, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>Last {{ $option }} Days</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2">
                        @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-3.5 h-3.5 text-slate-400'])
                    </span>
                </span>
            </div>
            <div class="h-64"><canvas id="userGrowthChart"></canvas></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Revenue Overview ({{ $days }} days)</h2>
                <span class="relative">
                    <select onchange="window.location.href = '{{ route('admin.dashboard') }}?range=' + this.value"
                            class="cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs font-medium text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                        @foreach ([7, 30, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>Last {{ $option }} Days</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2">
                        @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-3.5 h-3.5 text-slate-400'])
                    </span>
                </span>
            </div>
            <div class="h-64"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>

    {{-- Zone donut + booking trend --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Listings by Zone (Top {{ max(1, count($listingsByZone)) }})</h2>
                @can('zones.view')
                    <a href="{{ route('admin.zones.index') }}"
                       class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-2 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-night-700 dark:bg-night-800 dark:text-slate-300 dark:hover:bg-night-700">
                        All Zones
                        @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-3.5 h-3.5 text-slate-400'])
                    </a>
                @endcan
            </div>

            @if (count($listingsByZone) > 0)
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div class="relative h-52 w-52 shrink-0"><canvas id="zoneChart"></canvas></div>
                    <div class="w-full min-w-0 flex-1 space-y-2.5">
                        @foreach ($listingsByZone as $index => $zone)
                            <div class="flex items-center gap-2.5 text-sm">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $zoneColors[$index % count($zoneColors)] }}"></span>
                                <span class="min-w-0 flex-1 truncate text-slate-600 dark:text-slate-300">{{ $zone['name'] }}</span>
                                <span class="shrink-0 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $zone['percent'] }}%</span>
                                <span class="shrink-0 text-xs text-slate-400 dark:text-night-500">({{ number_format($zone['count']) }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="py-16 text-center text-sm text-slate-400">No zone data yet.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Booking Trend ({{ $days }} days)</h2>
                <span class="relative">
                    <select onchange="window.location.href = '{{ route('admin.dashboard') }}?range=' + this.value"
                            class="cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs font-medium text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300">
                        @foreach ([7, 30, 90] as $option)
                            <option value="{{ $option }}" @selected($days === $option)>Last {{ $option }} Days</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2">
                        @include('admin.partials.icon', ['name' => 'chevron-down', 'class' => 'w-3.5 h-3.5 text-slate-400'])
                    </span>
                </span>
            </div>

            {{-- Legend --}}
            <div class="mb-3 flex flex-wrap items-center justify-center gap-6 text-xs font-medium text-slate-600 dark:text-slate-300">
                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-[3px] border-2 border-indigo-500"></span> Total Bookings</span>
                <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-[3px] border-2 border-emerald-500"></span> Successful Bookings</span>
            </div>
            <div class="h-52"><canvas id="bookingChart"></canvas></div>
        </div>
    </div>

    {{-- Recent activity + recent listings --}}
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Recent Activity</h2>
                @can('notifications.view')
                    <a href="{{ route('admin.notifications.index') }}"
                       class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-brand transition-colors hover:bg-indigo-100 dark:bg-brand/15 dark:text-indigo-300 dark:hover:bg-brand/25">
                        View All
                    </a>
                @endcan
            </div>
            <div class="divide-y divide-slate-100 dark:divide-night-800">
                @forelse ($recentActivity as $event)
                    @php $eventStyle = $activityStyles[$event['type']] ?? $activityStyles['user']; @endphp
                    <div class="flex items-center gap-3.5 py-3.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $eventStyle['style'] }}">
                            @include('admin.partials.icon', ['name' => $eventStyle['icon'], 'class' => 'w-4.5 h-4.5'])
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $event['title'] }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $event['description'] }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400 dark:text-night-500">{{ \Illuminate\Support\Carbon::parse($event['created_at'])->diffForHumans(['parts' => 1, 'short' => true]) }}</span>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-slate-400">No recent activity.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 dark:border-night-700 dark:bg-night-900">
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 class="text-[15px] font-bold text-slate-900 dark:text-white">Recent Listings</h2>
                @can('listings.view')
                    <a href="{{ route('admin.listings.index') }}"
                       class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-brand transition-colors hover:bg-indigo-100 dark:bg-brand/15 dark:text-indigo-300 dark:hover:bg-brand/25">
                        View All
                    </a>
                @endcan
            </div>
            <div class="divide-y divide-slate-100 dark:divide-night-800">
                @forelse ($recentListings as $listing)
                    @php
                        $image = $listing['cover_image'][0] ?? $listing['images'][0] ?? null;
                        $imageUrl = $image
                            ? ((($image['disk'] ?? null) === 'remote' || str_starts_with($image['path'], 'http'))
                                ? $image['path']
                                : asset('storage/'.$image['path']))
                            : null;
                        $badge = $statusBadges[$listing['status']] ?? ['label' => ucfirst($listing['status']), 'style' => 'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300'];
                    @endphp
                    <div class="flex items-center gap-3 py-3">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $listing['title'] }}"
                                 class="h-11 w-14 shrink-0 rounded-lg object-cover" loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                            </div>
                        @else
                            <div class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $listing['title'] }}</p>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                @include('admin.partials.icon', ['name' => 'map-pin', 'class' => 'w-3.5 h-3.5 shrink-0 text-amber-500'])
                                <span class="truncate">{{ $listing['zone']['name'] ?? $listing['address'] ?? '—' }}</span>
                            </p>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ money($listing['price']) }} <span class="text-xs font-normal text-slate-400">/ month</span></p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['style'] }}">{{ $badge['label'] }}</span>
                        <span class="hidden shrink-0 text-xs text-slate-400 xl:block dark:text-night-500">{{ \Illuminate\Support\Carbon::parse($listing['created_at'])->format('M d, Y') }}</span>
                        <div class="relative shrink-0" x-data="kebabMenu()" @click.away="open = false">
                            <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                            </button>
                            <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-36 rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-night-700 dark:bg-night-800">
                                @can('listings.view')
                                    <a href="{{ route('admin.listings.show', $listing['id']) }}"
                                       class="flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                        @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                        View
                                    </a>
                                @endcan
                                @can('listings.edit')
                                    <a href="{{ route('admin.listings.edit', $listing['id']) }}"
                                       class="flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                        @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                        Edit
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-slate-400">No listings yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const dashboardData = {
            labels: @json(array_map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('M d'), array_keys($userGrowth))),
            users: @json(array_values($userGrowth)),
            revenue: @json(array_map('floatval', array_values($revenueOverTime))),
            zoneLabels: @json(array_column($listingsByZone, 'name')),
            zoneValues: @json(array_column($listingsByZone, 'count')),
            zoneColors: @json($zoneColors),
            bookingLabels: @json(array_map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('M d'), array_keys($bookingTrend))),
            bookingTotal: @json(array_column($bookingTrend, 'total')),
            bookingSuccessful: @json(array_column($bookingTrend, 'successful')),
            currencySymbol: @json($currencySymbol),
        };

        let dashboardCharts = [];

        function buildDashboardCharts() {
            if (typeof Chart === 'undefined') {
                return;
            }

            dashboardCharts.forEach((chart) => chart.destroy());
            dashboardCharts = [];

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(15,23,42,0.06)';
            const textColor = isDark ? '#8b91b8' : '#94a3b8';
            const surfaceColor = isDark ? '#10122b' : '#ffffff';

            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.font.size = 11;

            const tooltipStyle = {
                backgroundColor: isDark ? '#181a38' : '#0f172a',
                titleFont: { weight: '600' },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
            };

            const axisDefaults = {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: textColor, maxTicksLimit: 7, maxRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    border: { display: false },
                    ticks: { color: textColor, maxTicksLimit: 6, precision: 0 },
                },
            };

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: tooltipStyle },
            };

            const areaGradient = (canvas, rgb) => {
                const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight);
                gradient.addColorStop(0, `rgba(${rgb}, 0.22)`);
                gradient.addColorStop(1, `rgba(${rgb}, 0)`);
                return gradient;
            };

            // ── User growth (indigo area) ──
            const userCanvas = document.getElementById('userGrowthChart');
            dashboardCharts.push(new Chart(userCanvas, {
                type: 'line',
                data: {
                    labels: dashboardData.labels,
                    datasets: [{
                        label: 'Users',
                        data: dashboardData.users,
                        borderColor: '#6366f1',
                        borderWidth: 2,
                        backgroundColor: areaGradient(userCanvas, '99, 102, 241'),
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2.5,
                        pointBackgroundColor: surfaceColor,
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 1.5,
                        pointHoverRadius: 4,
                    }],
                },
                options: { ...baseOptions, scales: axisDefaults },
            }));

            // ── Revenue (emerald line) ──
            const revenueCanvas = document.getElementById('revenueChart');
            dashboardCharts.push(new Chart(revenueCanvas, {
                type: 'line',
                data: {
                    labels: dashboardData.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: dashboardData.revenue,
                        borderColor: '#10b981',
                        borderWidth: 2,
                        backgroundColor: areaGradient(revenueCanvas, '16, 185, 129'),
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointBackgroundColor: surfaceColor,
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 1.5,
                        pointHoverRadius: 4,
                    }],
                },
                options: {
                    ...baseOptions,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...tooltipStyle,
                            callbacks: { label: (ctx) => dashboardData.currencySymbol + Number(ctx.parsed.y).toLocaleString() },
                        },
                    },
                    scales: {
                        ...axisDefaults,
                        y: {
                            ...axisDefaults.y,
                            ticks: {
                                color: textColor,
                                maxTicksLimit: 6,
                                callback: (value) => value >= 1000
                                    ? dashboardData.currencySymbol + (value / 1000) + 'K'
                                    : dashboardData.currencySymbol + value,
                            },
                        },
                    },
                },
            }));

            // ── Listings by zone (doughnut) ──
            const zoneCanvas = document.getElementById('zoneChart');
            if (zoneCanvas && dashboardData.zoneValues.length) {
                dashboardCharts.push(new Chart(zoneCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: dashboardData.zoneLabels,
                        datasets: [{
                            data: dashboardData.zoneValues,
                            backgroundColor: dashboardData.zoneColors,
                            borderColor: surfaceColor,
                            borderWidth: 3,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: { legend: { display: false }, tooltip: tooltipStyle },
                    },
                }));
            }

            // ── Booking trend (total vs successful) ──
            const bookingCanvas = document.getElementById('bookingChart');
            dashboardCharts.push(new Chart(bookingCanvas, {
                type: 'line',
                data: {
                    labels: dashboardData.bookingLabels,
                    datasets: [
                        {
                            label: 'Total Bookings',
                            data: dashboardData.bookingTotal,
                            borderColor: '#6366f1',
                            borderWidth: 2,
                            backgroundColor: areaGradient(bookingCanvas, '99, 102, 241'),
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointBackgroundColor: surfaceColor,
                            pointBorderColor: '#6366f1',
                            pointBorderWidth: 1.5,
                        },
                        {
                            label: 'Successful Bookings',
                            data: dashboardData.bookingSuccessful,
                            borderColor: '#10b981',
                            borderWidth: 2,
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0.4,
                            pointRadius: 2,
                            pointBackgroundColor: surfaceColor,
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 1.5,
                        },
                    ],
                },
                options: {
                    ...baseOptions,
                    plugins: { legend: { display: false }, tooltip: { ...tooltipStyle, displayColors: true } },
                    scales: axisDefaults,
                },
            }));
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                document.querySelector('script[src*="chart.js"]')?.addEventListener('load', buildDashboardCharts);
                return;
            }
            buildDashboardCharts();
        });

        window.addEventListener('theme-changed', buildDashboardCharts);
    </script>
    @endpush
</x-admin-layout>
