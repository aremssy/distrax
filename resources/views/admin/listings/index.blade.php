<x-admin-layout title="All Listings">
    @php
        $typeMeta = [
            'rent'  => ['label' => 'Rent',  'icon' => 'building-office-2', 'style' => 'bg-indigo-50 text-indigo-500 dark:bg-indigo-500/15 dark:text-indigo-400'],
            'sale'  => ['label' => 'Sale',  'icon' => 'home-modern',       'style' => 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15 dark:text-emerald-400'],
            'hotel' => ['label' => 'Hotel', 'icon' => 'key',               'style' => 'bg-orange-50 text-orange-500 dark:bg-orange-500/15 dark:text-orange-400'],
            'mess'  => ['label' => 'Mess',  'icon' => 'users',             'style' => 'bg-sky-50 text-sky-500 dark:bg-sky-500/15 dark:text-sky-400'],
            'land'  => ['label' => 'Land',  'icon' => 'map',               'style' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-400'],
            'commercial' => ['label' => 'Commercial', 'icon' => 'building-office', 'style' => 'bg-teal-50 text-teal-500 dark:bg-teal-500/15 dark:text-teal-400'],
            'office' => ['label' => 'Office', 'icon' => 'briefcase',        'style' => 'bg-cyan-50 text-cyan-500 dark:bg-cyan-500/15 dark:text-cyan-400'],
            'room'  => ['label' => 'Room',  'icon' => 'users',             'style' => 'bg-fuchsia-50 text-fuchsia-500 dark:bg-fuchsia-500/15 dark:text-fuchsia-400'],
            'vacation' => ['label' => 'Vacation', 'icon' => 'sun',         'style' => 'bg-rose-50 text-rose-500 dark:bg-rose-500/15 dark:text-rose-400'],
            'parking' => ['label' => 'Parking', 'icon' => 'truck',         'style' => 'bg-slate-100 text-slate-500 dark:bg-night-800 dark:text-slate-300'],
        ];

        $statusBadges = [
            'active'   => ['label' => 'Published', 'style' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
            'pending'  => ['label' => 'Pending',   'style' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400'],
            'archived' => ['label' => 'Archived',  'style' => 'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300'],
            'rejected' => ['label' => 'Rejected',  'style' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400'],
            'rented'   => ['label' => 'Rented',    'style' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400'],
            'sold'     => ['label' => 'Sold',      'style' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400'],
        ];

        $avatarStyles = [
            'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
            'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
            'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
            'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        ];

        $priceSuffix = fn (string $type) => \App\Enums\PropertyType::tryFrom($type)?->priceSuffix();

        $sortUrl = function (string $key) {
            $next = (request('sort') === $key && request('dir', 'asc') === 'asc') ? 'desc' : 'asc';

            return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next, 'page' => null]);
        };

        $sortIcon = fn (string $key) => request('sort') !== $key
            ? ['name' => 'chevron-up-down', 'class' => 'w-3.5 h-3.5 text-slate-300 dark:text-night-600']
            : ['name' => request('dir', 'asc') === 'asc' ? 'chevron-up' : 'chevron-down', 'class' => 'w-3 h-3 text-brand'];

        $hasFilters = request()->hasAny(['search', 'type', 'status', 'zone_id', 'owner_id', 'featured', 'verified', 'stale']);
    @endphp

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ request()->boolean('trashed') ? 'Trashed Listings' : 'All Listings' }}</h1>
            <nav class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-400 dark:text-night-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-300">Home</a>
                @include('admin.partials.icon', ['name' => 'chevron-right', 'class' => 'w-3 h-3'])
                <span>Listings</span>
                @include('admin.partials.icon', ['name' => 'chevron-right', 'class' => 'w-3 h-3'])
                <span class="font-medium text-slate-500 dark:text-slate-300">{{ request()->boolean('trashed') ? 'Trash' : 'All Listings' }}</span>
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if (request()->boolean('trashed'))
                <a href="{{ route('admin.listings.index') }}"
                   class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'arrow-left', 'class' => 'w-4 h-4 text-slate-400'])
                    Back to Active
                </a>
            @else
                <a href="{{ route('admin.listings.index', ['trashed' => 1]) }}" title="View trash"
                   class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                    @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4 text-slate-400'])
                </a>
            @endif

            <a href="{{ route('admin.listings.export', request()->query()) }}"
               class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800">
                @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4 text-slate-400'])
                Export
            </a>

            @can('listings.create')
                <a href="{{ route('admin.listings.create') }}"
                   class="flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                    @include('admin.partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                    Add Listing
                </a>
            @endcan
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.listings.index') }}" x-data="{ showExtra: {{ request()->hasAny(['featured', 'verified', 'stale']) ? 'true' : 'false' }} }"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-night-700 dark:bg-night-900">
        @if (request()->boolean('trashed'))
            <input type="hidden" name="trashed" value="1">
        @endif
        @if (request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="dir" value="{{ request('dir', 'asc') }}">
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-52 flex-1">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search listings..."
                       class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm text-slate-700 placeholder-slate-400 focus:border-brand/50 focus:outline-none focus:ring-2 focus:ring-brand/10 dark:border-night-700 dark:bg-night-800 dark:text-slate-200 dark:placeholder-night-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Search">
                    @include('admin.partials.icon', ['name' => 'magnifying-glass', 'class' => 'w-4.5 h-4.5'])
                </button>
            </div>

            <select name="zone_id" onchange="this.form.submit()"
                    class="w-full cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none sm:w-auto dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Zones</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>{{ $zone->name }}</option>
                @endforeach
            </select>

            <select name="type" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">All Types</option>
                @foreach (\App\Enums\PropertyType::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()"
                    class="cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">Status</option>
                @foreach ($statusBadges as $value => $badge)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $badge['label'] }}</option>
                @endforeach
            </select>

            <select name="owner_id" onchange="this.form.submit()"
                    class="max-w-44 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-9 text-sm text-slate-600 focus:border-brand/50 focus:outline-none dark:border-night-700 dark:bg-night-800 dark:text-slate-300"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 0.9rem;">
                <option value="">Published By</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(request('owner_id') == $owner->id)>{{ $owner->name }}</option>
                @endforeach
            </select>

            <div class="ml-auto flex items-center gap-2">
                @if ($hasFilters)
                    <a href="{{ route('admin.listings.index', request()->only('trashed') ?: []) }}"
                       class="flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                        @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                        Clear
                    </a>
                @endif
                <button type="button" @click="showExtra = !showExtra"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-night-700 dark:bg-night-900 dark:text-slate-200 dark:hover:bg-night-800"
                        :class="showExtra && 'border-brand/40 text-brand dark:text-indigo-300'">
                    @include('admin.partials.icon', ['name' => 'funnel', 'class' => 'w-4 h-4 text-slate-400'])
                    Filters
                </button>
            </div>
        </div>

        {{-- Extra filters --}}
        <div x-cloak x-show="showExtra" class="mt-4 flex flex-wrap items-center gap-5 border-t border-slate-100 pt-4 dark:border-night-800">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="featured" value="1" @checked(request()->boolean('featured'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Featured only
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="verified" value="1" @checked(request()->boolean('verified'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Verified only
            </label>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="stale" value="1" @checked(request()->boolean('stale'))
                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                Stale ({{ $freshnessDays }}d+)
            </label>
            <button type="submit"
                    class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white shadow-md shadow-brand/30 transition-colors hover:bg-indigo-700">
                Apply
            </button>
        </div>
    </form>

    {{-- Table --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-night-700 dark:bg-night-900"
         x-data="{
             ids: [],
             allIds: @js($listings->pluck('id')->map(fn ($id) => (string) $id)->all()),
             toggleAll(event) { this.ids = event.target.checked ? [...this.allIds] : []; },
             submitBulk(formId) {
                 const form = document.getElementById(formId);
                 form.querySelectorAll('input[name=\'ids[]\']').forEach((el) => el.remove());
                 this.ids.forEach((id) => {
                     const input = document.createElement('input');
                     input.type = 'hidden';
                     input.name = 'ids[]';
                     input.value = id;
                     form.appendChild(input);
                 });
                 form.submit();
             },
         }">

        {{-- Bulk actions bar --}}
        <div x-cloak x-show="ids.length > 0" class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-indigo-50/60 px-5 py-3 dark:border-night-800 dark:bg-brand/10">
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200"><span x-text="ids.length"></span> selected</p>
            @can('listings.approve')
                @unless (request()->boolean('trashed'))
                    <button type="button" @click="submitBulk('bulk-approve-form')"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                        Approve Pending
                    </button>
                @endunless
            @endcan
            @can('listings.delete')
                @unless (request()->boolean('trashed'))
                    <button type="button"
                            @click="window.adminConfirm({ text: `Move ${ids.length} selected listing(s) to trash?`, confirmText: 'Yes, move to trash' }).then(r => r.isConfirmed && submitBulk('bulk-trash-form'))"
                            class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-rose-700">
                        Move to Trash
                    </button>
                @endunless
            @endcan
            <button type="button" @click="ids = []"
                    class="ml-auto text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                Clear selection
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 dark:border-night-800 dark:bg-night-800/50">
                        <th class="w-12 px-5 py-3.5">
                            <input type="checkbox" @change="toggleAll($event)" :checked="ids.length === allIds.length && allIds.length > 0"
                                   class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        </th>
                        <th class="px-3 py-3.5 text-left text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Listing</th>
                        @foreach ([['zone', 'Zone'], ['type', 'Type'], ['price', 'Price'], ['status', 'Status'], ['owner', 'Published By'], ['published', 'Published On']] as [$key, $label])
                            <th class="whitespace-nowrap px-3 py-3.5 text-left">
                                <a href="{{ $sortUrl($key) }}" class="group inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 dark:text-night-500 dark:hover:text-slate-300">
                                    {{ $label }}
                                    @include('admin.partials.icon', $sortIcon($key))
                                </a>
                            </th>
                        @endforeach
                        <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-night-800">
                    @forelse ($listings as $listing)
                        @php
                            $image = $listing->coverImage->first() ?? $listing->images->first();
                            $imageUrl = $image
                                ? (($image->disk === 'remote' || str_starts_with($image->path, 'http')) ? $image->path : asset('storage/'.$image->path))
                                : null;
                            $type = $typeMeta[$listing->type] ?? ['label' => ucfirst($listing->type), 'icon' => 'building-office-2', 'style' => 'bg-slate-100 text-slate-500 dark:bg-night-800 dark:text-slate-400'];
                            $badge = $statusBadges[$listing->status] ?? ['label' => ucfirst($listing->status), 'style' => 'bg-slate-100 text-slate-600 dark:bg-night-800 dark:text-slate-300'];
                            $avatarStyle = $avatarStyles[crc32($listing->owner->name ?? '—') % count($avatarStyles)];
                            $suffix = $priceSuffix($listing->type);
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 dark:hover:bg-night-800/40 {{ $listing->trashed() ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3.5">
                                <input type="checkbox" value="{{ $listing->id }}" x-model="ids"
                                       class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                            </td>

                            <td class="min-w-60 max-w-72 px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $listing->title }}" loading="lazy"
                                             class="h-11 w-14 shrink-0 rounded-lg object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @else
                                        <div class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-night-800 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'photo', 'class' => 'w-5 h-5'])
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.listings.show', $listing->id) }}"
                                           class="block truncate text-sm font-semibold text-slate-800 transition-colors hover:text-brand dark:text-slate-100 dark:hover:text-indigo-300">
                                            {{ $listing->title }}
                                        </a>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400 dark:text-night-500">
                                            @include('admin.partials.icon', ['name' => 'map-pin', 'class' => 'w-3.5 h-3.5 shrink-0 text-amber-500'])
                                            <span class="truncate">{{ $listing->address ?? $listing->zone?->name ?? '—' }}</span>
                                        </p>
                                        @if ($listing->is_featured || $listing->is_verified || $listing->reports_count > 0)
                                            <p class="mt-1 flex flex-wrap items-center gap-1.5">
                                                @if ($listing->is_featured)
                                                    <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">★ Featured</span>
                                                @endif
                                                @if ($listing->is_verified)
                                                    <span class="rounded-full bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold text-sky-600 dark:bg-sky-500/15 dark:text-sky-400">✓ Verified</span>
                                                @endif
                                                @if ($listing->reports_count > 0)
                                                    <a href="{{ route('admin.listings.reports.index', ['status' => 'pending']) }}"
                                                       class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-400">
                                                        {{ $listing->reports_count }} report{{ $listing->reports_count > 1 ? 's' : '' }}
                                                    </a>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-600 dark:text-slate-300">
                                {{ $listing->zone?->name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $type['style'] }}">
                                        @include('admin.partials.icon', ['name' => $type['icon'], 'class' => 'w-4 h-4'])
                                    </span>
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $type['label'] }}</span>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ moneyFrom($listing->price, $listing->currency_code) }}</span>
                                @if ($suffix)
                                    <span class="text-xs text-slate-400 dark:text-night-500">{{ $suffix }}</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['style'] }}">{{ $badge['label'] }}</span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold {{ $avatarStyle }}">
                                        {{ strtoupper(substr($listing->owner->name ?? '—', 0, 2)) }}
                                    </span>
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $listing->owner?->name ?? '—' }}</span>
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-sm text-slate-500 dark:text-slate-400">
                                {{ ($listing->published_at ?? $listing->created_at)?->format('M d, Y') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <div class="relative inline-block" x-data="kebabMenu()" @click.away="open = false">
                                    <button type="button" @click="open = !open" aria-haspopup="menu" :aria-expanded="open" aria-label="Row actions"
                                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-night-800 dark:hover:text-slate-300">
                                        @include('admin.partials.icon', ['name' => 'ellipsis-vertical', 'class' => 'w-4.5 h-4.5'])
                                    </button>
                                    <div x-cloak x-show="open" x-transition.opacity.duration.150ms :style="coords" data-kebab-menu
                                         class="fixed z-50 w-44 rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl dark:border-night-700 dark:bg-night-800">
                                        <a href="{{ route('admin.listings.show', $listing->id) }}"
                                           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                            @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4 text-slate-400'])
                                            View
                                        </a>

                                        @if ($listing->trashed())
                                            @can('listings.edit')
                                                <form method="POST" action="{{ route('admin.listings.restore', $listing->id) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                        @include('admin.partials.icon', ['name' => 'arrow-uturn-left', 'class' => 'w-4 h-4'])
                                                        Restore
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('listings.delete')
                                                <form method="POST" action="{{ route('admin.listings.destroy', $listing->id) }}"
                                                      data-confirm="Permanently delete this listing?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Delete Forever
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('listings.edit')
                                                <a href="{{ route('admin.listings.edit', $listing) }}"
                                                   class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                    @include('admin.partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4 text-slate-400'])
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('listings.approve')
                                                @if ($listing->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.listings.approve', $listing) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                            @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                                                            Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.listings.reject', $listing) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                            @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                                            Reject
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            @can('listings.edit')
                                                @if ($listing->status === 'active')
                                                    <form method="POST" action="{{ route('admin.listings.archive', $listing) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-night-700/60">
                                                            @include('admin.partials.icon', ['name' => 'arrow-down-tray', 'class' => 'w-4 h-4 text-slate-400'])
                                                            Archive
                                                        </button>
                                                    </form>
                                                @elseif ($listing->status === 'archived')
                                                    <form method="POST" action="{{ route('admin.listings.approve', $listing) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                                                            @include('admin.partials.icon', ['name' => 'arrow-uturn-left', 'class' => 'w-4 h-4'])
                                                            Restore
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            @can('listings.delete')
                                                <div class="my-1 border-t border-slate-100 dark:border-night-700"></div>
                                                <form method="POST" action="{{ route('admin.listings.destroy', $listing->id) }}"
                                                      data-confirm="Move to trash?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                                        @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                                        Move to Trash
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center text-sm text-slate-400">No listings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer: entries + pagination --}}
        {{ $listings->onEachSide(1)->links('admin.partials.pagination') }}
    </div>

    {{-- Bulk action forms --}}
    @can('listings.approve')
        <form id="bulk-approve-form" method="POST" action="{{ route('admin.listings.bulk-approve') }}" class="hidden">@csrf</form>
    @endcan
    @can('listings.delete')
        <form id="bulk-trash-form" method="POST" action="{{ route('admin.listings.bulk-trash') }}" class="hidden">@csrf</form>
    @endcan
</x-admin-layout>
