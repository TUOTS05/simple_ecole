@extends('layouts.app')

@section('title', 'Suivi du bus')
@section('page_title', 'Suivi du bus')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📍 Suivi du bus</h1>
            <p class="text-sm text-gray-500">{{ $subscription->extra->name }} — {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <a href="{{ route('parent.extras.index', $student->id) }}" class="text-primary hover:underline text-sm font-semibold">← Mes extras</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="text-sm text-gray-600 mb-3">
            🚌 <strong>{{ $subscription->transportAssignment->vehicle->plate_number }}</strong>
            @if($subscription->transportAssignment->route)
            — Circuit {{ $subscription->transportAssignment->route->name }}
            @endif
            @if($subscription->transportAssignment->stop)
            — Arrêt : {{ $subscription->transportAssignment->stop->label }}
            @endif
        </div>

        <div id="status" class="text-sm font-semibold mb-3"></div>

        <div id="map" style="height: 400px;" class="rounded-lg border border-gray-200 z-0"></div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map').setView([5.3600, -4.0083], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    let marker = null;
    const statusEl = document.getElementById('status');

    async function refresh() {
        try {
            const response = await fetch("{{ route('parent.extras.track-bus.data', ['student' => $student->id, 'subscriptionId' => $subscription->id]) }}");
            const data = await response.json();

            if (! data.available) {
                statusEl.innerHTML = '<span class="text-gray-500">⏳ Le chauffeur n\'a pas encore démarré le partage de position aujourd\'hui.</span>';
                return;
            }

            const latlng = [data.latitude, data.longitude];
            if (marker) {
                marker.setLatLng(latlng);
            } else {
                marker = L.marker(latlng).addTo(map);
                map.setView(latlng, 15);
            }

            statusEl.innerHTML = data.stale
                ? '<span class="text-orange-600">⚠️ Dernière position obsolète (' + data.last_location_at + ')</span>'
                : '<span class="text-green-600">🟢 Position en direct — ' + data.last_location_at + '</span>';
        } catch (e) { console.error(e); }
    }

    refresh();
    setInterval(refresh, 15000);
</script>
@endpush
@endsection
