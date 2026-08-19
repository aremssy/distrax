<x-admin-layout title="Add Zone">
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    @endpush

    <x-admin-page-header title="Add Zone"
        :breadcrumbs="[['label' => 'Zones', 'route' => 'admin.zones.index'], ['label' => 'Add']]" />

    <form method="POST" action="{{ route('admin.zones.store') }}">
        @csrf
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
            <x-admin-card title="Zone Details">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Name --}}
                    <div class="md:col-span-2">
                        <x-admin.form.input name="name" label="Name"
                            :value="old('name')" placeholder="e.g. Dhaka" required />
                    </div>

                    {{-- Type --}}
                    <x-admin.form.select name="type" label="Type" required
                        x-data x-on:change="$dispatch('zone-type-changed', { type: $event.target.value })">
                        @foreach(['country', 'state', 'city', 'area'] as $t)
                            <option value="{{ $t }}" @selected(old('type', $parent?->type === 'country' ? 'city' : (
                                $parent?->type === 'city' ? 'area' : 'country'
                            )) === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </x-admin.form.select>

                    {{-- Parent --}}
                    <x-admin.form.select name="parent_id" label="Parent Zone"
                        hint="Leave empty for top-level (country)">
                        <option value="">— None (top level) —</option>
                        @foreach($parents->groupBy('type') as $type => $group)
                            <optgroup label="{{ ucfirst($type) }}">
                                @foreach($group as $p)
                                    <option value="{{ $p->id }}"
                                        @selected(old('parent_id', $parent?->id) == $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-admin.form.select>

                    {{-- Sort order --}}
                    <x-admin.form.input name="sort_order" label="Sort Order" type="number"
                        :value="old('sort_order', 0)" min="0"
                        hint="Lower numbers appear first" />

                    {{-- Status --}}
                    <div class="flex items-center gap-2 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1"
                                @checked(old('is_active', true))
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Active</span>
                        </label>
                    </div>
                </div>
            </x-admin-card>

            {{-- Map picker --}}
            <x-admin-card title="Location (optional)">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">
                    Click on the map to set the representative lat/lng point for this zone.
                </p>

                <div class="grid grid-cols-2 gap-4 mb-3">
                    <x-admin.form.input name="lat" id="lat-input" label="Latitude"
                        :value="old('lat')" placeholder="e.g. 23.8103" type="number" step="0.0000001" />
                    <x-admin.form.input name="lng" id="lng-input" label="Longitude"
                        :value="old('lng')" placeholder="e.g. 90.4125" type="number" step="0.0000001" />
                </div>

                <div id="zone-map"
                     class="w-full rounded-lg border border-slate-200 dark:border-night-700 overflow-hidden"
                     style="height: 320px; z-index: 0;"></div>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-4 mb-2">
                    Optionally draw the zone's boundary polygon (used for map-bounds and draw-area search). Use the polygon tool in the top-right of the map above.
                </p>
                <input type="hidden" name="boundary" id="boundary-input" value="{{ old('boundary') }}">
            </x-admin-card>
        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                Add Zone
            </button>
            <a href="{{ route('admin.zones.index') }}"
               class="px-5 py-2 border border-slate-200 dark:border-night-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-night-800 transition">
                Cancel
            </a>
        </div>
    </form>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
        <script>
        (function () {
            const tileUrl   = @json(setting('map_tile_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'));
            const tileAttr  = @json(setting('map_tile_attribution', '© OpenStreetMap contributors'));
            const latInput  = document.getElementById('lat-input');
            const lngInput  = document.getElementById('lng-input');
            const boundaryInput = document.getElementById('boundary-input');

            const initLat = parseFloat(latInput.value) || 23.685;
            const initLng = parseFloat(lngInput.value) || 90.356;

            const map = L.map('zone-map').setView([initLat, initLng], 6);
            L.tileLayer(tileUrl, { attribution: tileAttr, maxZoom: 18 }).addTo(map);

            const drawnItems = new L.FeatureGroup();
            map.addLayer(drawnItems);

            if (boundaryInput.value) {
                try {
                    const coords = JSON.parse(boundaryInput.value);
                    const polygon = L.polygon(coords).addTo(drawnItems);
                    map.fitBounds(polygon.getBounds());
                } catch (e) { /* no valid boundary yet */ }
            }

            const drawControl = new L.Control.Draw({
                draw: { polygon: true, marker: false, circle: false, circlemarker: false, polyline: false, rectangle: true },
                edit: { featureGroup: drawnItems },
            });
            map.addControl(drawControl);

            function syncBoundary() {
                const layer = drawnItems.getLayers()[0];
                boundaryInput.value = layer ? JSON.stringify(layer.getLatLngs()[0].map(p => [p.lat, p.lng])) : '';
            }

            map.on(L.Draw.Event.CREATED, e => {
                drawnItems.clearLayers();
                drawnItems.addLayer(e.layer);
                syncBoundary();
            });
            map.on(L.Draw.Event.EDITED, syncBoundary);
            map.on(L.Draw.Event.DELETED, syncBoundary);

            let marker = null;

            function placeMarker(lat, lng) {
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', e => {
                    const pos = e.target.getLatLng();
                    latInput.value = pos.lat.toFixed(7);
                    lngInput.value = pos.lng.toFixed(7);
                });
            }

            if (latInput.value && lngInput.value) {
                placeMarker(parseFloat(latInput.value), parseFloat(lngInput.value));
            }

            map.on('click', e => {
                placeMarker(e.latlng.lat, e.latlng.lng);
                latInput.value = e.latlng.lat.toFixed(7);
                lngInput.value = e.latlng.lng.toFixed(7);
            });

            // Sync manual input to map marker
            [latInput, lngInput].forEach(el => {
                el.addEventListener('change', () => {
                    const lat = parseFloat(latInput.value);
                    const lng = parseFloat(lngInput.value);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        placeMarker(lat, lng);
                        map.setView([lat, lng], map.getZoom());
                    }
                });
            });
        })();
        </script>
    @endpush
</x-admin-layout>
