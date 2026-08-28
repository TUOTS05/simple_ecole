@extends('layouts.app')

@section('title', 'Circuits Transport')
@section('page_title', 'Circuits et arrêts')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Extra (transport) *</label>
                <select name="extra_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($extras as $extra)
                    <option value="{{ $extra->id }}" {{ $extraId == $extra->id ? 'selected' : '' }}>{{ $extra->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($extraId)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Nouveau circuit</h3>
        <form action="{{ route('extras.transport.routes.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            <input type="hidden" name="extra_id" value="{{ $extraId }}">
            <input type="text" name="name" required maxlength="150" placeholder="Nom du circuit (ex: Circuit A)" class="md:col-span-2 px-4 py-2 border border-gray-300 rounded-lg">
            <select name="extra_vehicle_id" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="">Aucun véhicule</option>
                @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} ({{ $vehicle->capacity }} places)</option>
                @endforeach
            </select>
            <button type="submit" class="md:col-span-1 bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Créer</button>
        </form>
    </div>

    @foreach($routes as $route)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h4 class="font-bold text-gray-800">{{ $route->name }} @if($route->vehicle)<span class="text-sm font-normal text-gray-500">— 🚌 {{ $route->vehicle->plate_number }}</span>@endif</h4>
            <form action="{{ route('extras.transport.routes.destroy', $route->id) }}" method="POST" onsubmit="return confirm('Supprimer ce circuit ?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Supprimer</button>
            </form>
        </div>

        <div class="space-y-2 mb-4">
            @forelse($route->stops as $stop)
            <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                <span>{{ $stop->order }}. {{ $stop->label }} @if($stop->pickup_time)<span class="text-xs text-gray-500">— {{ \Carbon\Carbon::parse($stop->pickup_time)->format('H:i') }}</span>@endif</span>
                <form action="{{ route('extras.transport.stops.destroy', $stop->id) }}" method="POST" onsubmit="return confirm('Supprimer cet arrêt ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">🗑️</button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500">Aucun arrêt défini.</p>
            @endforelse
        </div>

        <form action="{{ route('extras.transport.stops.store', $route->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <input type="text" name="label" required maxlength="150" placeholder="Point d'arrêt (ex: Angré)" class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
            <input type="number" name="order" min="0" placeholder="Ordre" class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="time" name="pickup_time" class="px-3 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="md:col-span-4 bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-semibold transition">+ Ajouter un arrêt</button>
        </form>
    </div>
    @endforeach
    @endif
</div>
@endsection
