@extends('layouts.app')

@section('title', 'Suivi GPS des véhicules')
@section('page_title', 'Suivi GPS des véhicules')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">🗺️ Position des véhicules actifs</h3>
                <p class="text-sm text-gray-500">Rafraîchi automatiquement toutes les 15 secondes. Un véhicule n'apparaît que si son chauffeur a démarré le partage de position.</p>
            </div>
            <a href="{{ route('extras.transport.vehicles.index') }}" class="text-primary hover:underline text-sm font-semibold">← Véhicules</a>
        </div>

        <div id="map" style="height: 500px;" class="rounded-lg border border-gray-200 z-0"></div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 pt-6"><h3 class="text-lg font-bold text-gray-800 mb-2">🔗 Liens de partage par véhicule</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Véhicule</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Conducteur</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($vehicles as $vehicle)
                <tr>
                    <td class="py-2 px-3 font-mono font-semibold">{{ $vehicle->plate_number }}</td>
                    <td class="py-2 px-3">{{ $vehicle->driver_name }}</td>
                    <td class="py-2 px-3 text-center">
                        <a href="{{ route('extras.transport.vehicles.tracking-link', $vehicle->id) }}" class="text-primary hover:underline">📱 Lien / QR chauffeur</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-8 text-center text-gray-500">Aucun véhicule actif.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map').setView([5.3600, -4.0083], 12); // Abidjan par défaut
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    const markers = {};

    function busIcon(stale) {
        return L.divIcon({
            html: `<div style="font-size: 28px; filter: ${stale ? 'grayscale(1); opacity: 0.6' : 'none'};">🚌</div>`,
            className: '',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        });
    }

    async function refresh() {
        try {
            const response = await fetch("{{ route('extras.transport.tracking.data') }}");
            const vehicles = await response.json();

            const bounds = [];
            vehicles.forEach((v) => {
                const latlng = [v.latitude, v.longitude];
                bounds.push(latlng);

                if (markers[v.id]) {
                    markers[v.id].setLatLng(latlng).setIcon(busIcon(v.stale));
                } else {
                    markers[v.id] = L.marker(latlng, { icon: busIcon(v.stale) }).addTo(map);
                }
                markers[v.id].bindPopup(
                    `<strong>${v.plate_number}</strong><br>${v.driver_name || ''}<br>` +
                    `<span style="color:${v.stale ? '#dc2626' : '#16a34a'}">${v.stale ? '⚠️ Position obsolète' : '🟢 En direct'}</span> — ${v.last_location_at || ''}`
                );
            });

            if (bounds.length > 0 && !window.__mapFitted) {
                map.fitBounds(bounds, { padding: [40, 40] });
                window.__mapFitted = true;
            }
        } catch (e) { console.error(e); }
    }

    refresh();
    setInterval(refresh, 15000);
</script>
@endpush
@endsection
