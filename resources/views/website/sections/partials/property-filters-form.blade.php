<form class="space-y-5" action="{{ route('properties.index') }}" method="GET" data-live-search="#property-results-count,#property-active-filters,#property-save-search,#property-results">
    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-white" for="keyword">Search</label>
        <input id="keyword" name="keyword" type="search" value="{{ $filters['keyword'] ?? '' }}" placeholder="Title, address, keyword"
            class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-white" for="zone_id">Location</label>
        <select id="zone_id" name="zone_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <option value="">All locations</option>
            @foreach ($zones as $zone)
                <option value="{{ $zone->id }}" @selected(($filters['zone_id'] ?? null) === $zone->id)>{{ $zone->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-white" for="type">Property type</label>
        <select id="type" name="type" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <option value="">All types</option>
            @foreach (\App\Enums\PropertyType::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <label class="text-sm font-semibold text-slate-800 dark:text-white">Minimum price
            <input name="min_price" type="number" min="0" value="{{ $filters['min_price'] ?? '' }}" placeholder="0"
                class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        </label>
        <label class="text-sm font-semibold text-slate-800 dark:text-white">Maximum price
            <input name="max_price" type="number" min="0" value="{{ $filters['max_price'] ?? '' }}" placeholder="Any"
                class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        </label>
    </div>

    <div class="grid grid-cols-2 gap-3">
        @foreach (['bedrooms' => [1, 2, 3, 4, 5], 'bathrooms' => [1, 2, 3, 4]] as $field => $options)
            <label class="text-sm font-semibold capitalize text-slate-800 dark:text-white">{{ $field }}
                <select name="{{ $field }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">Any</option>
                    @foreach ($options as $count)
                        <option value="{{ $count }}" @selected(($filters[$field] ?? null) === $count)>{{ $count }}+</option>
                    @endforeach
                </select>
            </label>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-3">
        @foreach (['furnished' => 'Furnished', 'parking' => 'Parking'] as $field => $label)
            <label class="text-sm font-semibold text-slate-800 dark:text-white">{{ $label }}
                <select name="{{ $field }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">Any</option>
                    <option value="1" @selected(($filters[$field] ?? null) === true)>Yes</option>
                    <option value="0" @selected(($filters[$field] ?? null) === false)>No</option>
                </select>
            </label>
        @endforeach
    </div>

    <div class="flex gap-3 pt-2">
        <button class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Apply filters</button>
        <a class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            href="{{ route('properties.index') }}">Reset</a>
    </div>
</form>
