<x-admin-layout :title="$listing->title">
    <x-admin-page-header :title="$listing->title"
        :breadcrumbs="[['label' => 'Listings', 'route' => 'admin.listings.index'], ['label' => '#'.$listing->id]]">
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                @can('listings.edit')
                    @unless($listing->trashed())
                        <a href="{{ route('admin.listings.edit', $listing) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                            Edit
                        </a>
                    @endunless

                    @if($listing->status === 'pending')
                        <form method="POST" action="{{ route('admin.listings.approve', $listing->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                @include('admin.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.listings.reject', $listing->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                @include('admin.partials.icon', ['name' => 'x-mark', 'class' => 'w-4 h-4'])
                                Reject
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.listings.feature', $listing->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border {{ $listing->is_featured ? 'border-yellow-400 bg-yellow-50 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400' : 'border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-300' }} text-sm font-medium rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-500/10 transition">
                            @include('admin.partials.icon', ['name' => 'star', 'class' => 'w-4 h-4'])
                            {{ $listing->is_featured ? 'Unfeature' : 'Feature' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.listings.verify', $listing->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border {{ $listing->is_verified ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' : 'border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-300' }} text-sm font-medium rounded-lg hover:bg-blue-50 dark:hover:bg-blue-500/10 transition">
                            @include('admin.partials.icon', ['name' => 'shield-check', 'class' => 'w-4 h-4'])
                            {{ $listing->is_verified ? 'Unverify' : 'Verify' }}
                        </button>
                    </form>

                    @if(in_array($listing->status, ['active', 'pending']))
                        <form method="POST" action="{{ route('admin.listings.archive', $listing->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                                Archive
                            </button>
                        </form>
                    @elseif($listing->status === 'archived')
                        <form method="POST" action="{{ route('admin.listings.approve', $listing->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 text-sm font-medium rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 transition">
                                Restore Active
                            </button>
                        </form>
                    @endif
                @endcan

                @can('listings.delete')
                    @unless($listing->trashed())
                        <form method="POST" action="{{ route('admin.listings.destroy', $listing->id) }}"
                              data-confirm="Move to trash?">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                                @include('admin.partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                Trash
                            </button>
                        </form>
                    @endunless
                @endcan
            </div>
        </x-slot>
    </x-admin-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Main info (2/3) --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Core details --}}
            <x-admin-card title="Listing Details">
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm">
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Type</dt>
                        <dd><x-admin-badge :color="$listing->typeColor()">{{ ucfirst($listing->type) }}</x-admin-badge></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Status</dt>
                        <dd>
                            <x-admin-badge :color="$listing->statusColor()">{{ ucfirst($listing->status) }}</x-admin-badge>
                            @if($listing->isStale())
                                <x-admin-badge color="yellow" class="ml-1">Stale</x-admin-badge>
                            @endif
                            @if($listing->needs_confirmation_at)
                                <x-admin-badge color="red" class="ml-1">Needs Confirmation</x-admin-badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Price</dt>
                        <dd class="font-semibold text-slate-800 dark:text-slate-100 font-mono">{{ moneyFrom($listing->price, $listing->currency_code) }}</dd>
                    </div>
                    @if($listing->service_charge)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Service Charge</dt>
                            <dd class="font-mono text-slate-700 dark:text-slate-200">{{ moneyFrom($listing->service_charge, $listing->currency_code) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Zone</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ $listing->zone?->name ?? '—' }}</dd>
                    </div>
                    @if($listing->address)
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Address</dt>
                            <dd class="break-words text-slate-700 dark:text-slate-200">{{ $listing->address }}</dd>
                        </div>
                    @endif
                    @if($listing->bedrooms !== null)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Bed / Bath</dt>
                            <dd>{{ $listing->bedrooms }}B / {{ $listing->bathrooms }}Ba</dd>
                        </div>
                    @endif
                    @if($listing->area_sqft)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Area</dt>
                            <dd>{{ number_format($listing->area_sqft) }} sqft</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Furnished</dt>
                        <dd>{{ $listing->furnished ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Parking</dt>
                        <dd>{{ $listing->parking ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Allowed for</dt>
                        <dd class="capitalize">{{ $listing->allowed_for }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Views</dt>
                        <dd>{{ number_format($listing->view_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Published</dt>
                        <dd>{{ $listing->published_at?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Last Refreshed</dt>
                        <dd>{{ $listing->last_freshness_check_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                    @if($listing->needs_confirmation_at)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">Needs Confirmation Since</dt>
                            <dd>{{ $listing->needs_confirmation_at->diffForHumans() }}</dd>
                        </div>
                    @endif
                </dl>

                @if($listing->description)
                    <div class="mt-5 pt-4 border-t border-slate-100 dark:border-night-700">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-2">Description</p>
                        @php
                            // Sanitized on save (SanitizedHtml cast); legacy plain text keeps its line breaks.
                            $descriptionHtml = strip_tags($listing->description) === $listing->description
                                ? nl2br(e($listing->description))
                                : $listing->description;
                        @endphp
                        <div class="break-words text-sm leading-relaxed text-slate-700 dark:text-slate-300
                            [&>p]:mb-3 [&_a]:font-medium [&_a]:text-brand [&_a]:underline [&_strong]:font-semibold
                            [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mb-1
                            [&_blockquote]:border-s-4 [&_blockquote]:border-slate-300 [&_blockquote]:ps-3 [&_blockquote]:italic">{!! $descriptionHtml !!}</div>
                    </div>
                @endif
            </x-admin-card>

            {{-- Custom field values --}}
            @if($listing->customFieldValues->isNotEmpty())
                <x-admin-card title="Custom Fields">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-4 text-sm">
                        @foreach($listing->customFieldValues as $cfv)
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 dark:text-night-500 mb-0.5">
                                    {{ $cfv->field?->label ?? $cfv->custom_field_id }}
                                </dt>
                                <dd class="break-words text-slate-700 dark:text-slate-200">{{ $cfv->value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-admin-card>
            @endif

            {{-- Images --}}
            @if($listing->images->isNotEmpty())
                <x-admin-card title="Images ({{ $listing->images->count() }})">
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach($listing->images as $img)
                            <div class="relative rounded-lg overflow-hidden bg-slate-100 dark:bg-night-800 aspect-video group">
                                <img src="{{ $img->url() }}"
                                     alt=""
                                     loading="lazy"
                                     class="w-full h-full object-cover"
                                     onerror="this.closest('div').innerHTML='<span class=\'p-2 text-xs text-slate-400\'>No preview</span>'">
                                @if($img->is_cover)
                                    <span class="absolute top-1 left-1 text-[10px] bg-indigo-600 text-white px-1 py-0.5 rounded font-semibold">Cover</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif

            {{-- Videos --}}
            @if($listing->videos->isNotEmpty())
                <x-admin-card title="Videos ({{ $listing->videos->count() }})">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($listing->videos as $vid)
                            <div class="overflow-hidden rounded-lg bg-slate-100 dark:bg-night-800">
                                @if($vid->path)
                                    <video controls preload="metadata" class="aspect-video w-full bg-black" src="{{ $vid->resolvedUrl() }}"></video>
                                @elseif($vid->embedUrl())
                                    <iframe src="{{ $vid->embedUrl() }}" class="aspect-video w-full" loading="lazy" allowfullscreen
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                                @else
                                    <a href="{{ $vid->resolvedUrl() }}" target="_blank" rel="noopener"
                                       class="flex aspect-video w-full items-center justify-center gap-2 text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                                        @include('admin.partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                                        Open video
                                    </a>
                                @endif
                                <p class="truncate px-2 py-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="font-semibold uppercase">{{ $vid->source ?? 'external' }}</span> · {{ $vid->resolvedUrl() }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif

            {{-- Reports on this listing --}}
            @if($listing->reports->isNotEmpty())
                <x-admin-card title="Reports ({{ $listing->reports->count() }})">
                    <div class="space-y-3">
                        @foreach($listing->reports as $report)
                            <div class="rounded-lg border border-slate-200 dark:border-night-700 p-3 text-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="font-medium text-slate-800 dark:text-slate-100">{{ $report->reason }}</span>
                                        <span class="text-slate-400 ml-2 text-xs">by {{ $report->reporter?->name ?? 'unknown' }}</span>
                                    </div>
                                    <x-admin-badge :color="match($report->status) {
                                        'pending' => 'yellow', 'actioned' => 'green', 'dismissed' => 'gray', default => 'gray'
                                    }">{{ ucfirst($report->status) }}</x-admin-badge>
                                </div>
                                @if($report->details)
                                    <p class="mt-1 break-words text-xs text-slate-500 dark:text-slate-400">{{ $report->details }}</p>
                                @endif
                                <p class="mt-1 text-xs text-slate-400">{{ $report->created_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-admin-card>
            @endif
        </div>

        {{-- ── Sidebar (1/3) --}}
        <div class="space-y-5">

            {{-- Owner card --}}
            <x-admin-card title="Owner">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-500/15 flex items-center justify-center shrink-0">
                        @include('admin.partials.icon', ['name' => 'user-circle', 'class' => 'w-6 h-6 text-indigo-600 dark:text-indigo-400'])
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ $listing->owner?->name ?? 'Unknown' }}</p>
                        <p class="break-all text-xs text-slate-500 sm:break-normal">{{ $listing->owner?->email }}</p>
                    </div>
                </div>
                @if($listing->agency)
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Agency: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $listing->agency->name ?? $listing->agency_id }}</span>
                    </p>
                @endif
            </x-admin-card>

            {{-- Media settings info --}}
            <x-admin-card title="Media Storage">
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Driver</dt>
                        <dd class="font-medium font-mono text-xs text-slate-700 dark:text-slate-200">{{ setting('storage_driver', 'local') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Max width</dt>
                        <dd class="font-medium text-xs text-slate-700 dark:text-slate-200">{{ setting('image_max_width', 1920) }}px</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Quality</dt>
                        <dd class="font-medium text-xs text-slate-700 dark:text-slate-200">{{ setting('image_quality', 85) }}%</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Images</dt>
                        <dd class="font-medium text-xs text-slate-700 dark:text-slate-200">{{ $listing->images->count() }} / {{ setting('listing_max_images', 10) }}</dd>
                    </div>
                </dl>
            </x-admin-card>

            {{-- Freshness --}}
            <x-admin-card title="Freshness">
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Window</dt>
                        <dd class="font-medium text-slate-700 dark:text-slate-200">{{ $freshnessDays }} days</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Last check</dt>
                        <dd class="font-medium text-xs text-slate-700 dark:text-slate-200">
                            {{ $listing->last_freshness_check_at?->diffForHumans() ?? 'Never' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Stale?</dt>
                        <dd>
                            @if($listing->isStale())
                                <x-admin-badge color="yellow">Yes</x-admin-badge>
                            @else
                                <x-admin-badge color="green">No</x-admin-badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-admin-card>

            {{-- Meta --}}
            <x-admin-card title="Meta">
                <dl class="text-xs space-y-1.5 text-slate-500 dark:text-slate-400">
                    <div class="flex justify-between gap-2">
                        <dt>ID</dt>
                        <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $listing->id }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Created</dt>
                        <dd>{{ $listing->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Updated</dt>
                        <dd>{{ $listing->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                    @if($listing->trashed())
                        <div class="flex justify-between gap-2 text-red-500">
                            <dt>Trashed</dt>
                            <dd>{{ $listing->deleted_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-admin-card>
        </div>
    </div>
</x-admin-layout>
