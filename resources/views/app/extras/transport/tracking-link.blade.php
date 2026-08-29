@extends('layouts.app')

@section('title', 'Lien de suivi — ' . $vehicle->plate_number)
@section('page_title', 'Lien de suivi GPS')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 text-center">
        <h3 class="text-lg font-bold text-gray-800 mb-1">🚌 {{ $vehicle->plate_number }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ $vehicle->driver_name ?? 'Conducteur non renseigné' }}</p>

        <div class="flex justify-center mb-6">
            <div class="p-4 bg-white border border-gray-200 rounded-lg inline-block">
                {!! $qrSvg !!}
            </div>
        </div>

        <p class="text-sm text-gray-600 mb-2">Le chauffeur scanne ce QR (ou ouvre le lien ci-dessous) sur son téléphone pour démarrer le partage de sa position pendant le trajet.</p>

        <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-6 break-all font-mono text-xs text-gray-700">
            {{ $trackingUrl }}
        </div>

        <div class="flex flex-col gap-3">
            <a href="{{ $trackingUrl }}" target="_blank" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Ouvrir la page chauffeur</a>
            <form action="{{ route('extras.transport.vehicles.regenerate-tracking-token', $vehicle->id) }}" method="POST" onsubmit="return confirm('Régénérer le lien ? L\'ancien lien/QR ne fonctionnera plus.')">
                @csrf @method('PATCH')
                <button type="submit" class="w-full bg-orange-100 hover:bg-orange-200 text-orange-700 px-6 py-2 rounded-lg font-semibold transition text-sm">🔄 Régénérer le lien</button>
            </form>
            <a href="{{ route('extras.transport.tracking.index') }}" class="text-primary hover:underline text-sm">← Retour au suivi GPS</a>
        </div>
    </div>
</div>
@endsection
