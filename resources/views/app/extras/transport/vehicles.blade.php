@extends('layouts.app')

@section('title', 'Véhicules Transport')
@section('page_title', 'Véhicules')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ editing: null }">

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
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Nouveau véhicule</h3>
        <form action="{{ route('extras.transport.vehicles.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <input type="text" name="plate_number" required maxlength="30" placeholder="Immatriculation" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg">
            <input type="number" name="capacity" required min="1" placeholder="Capacité" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="driver_name" maxlength="150" placeholder="Conducteur" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="driver_phone" maxlength="30" placeholder="Tél. conducteur" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="assistant_name" maxlength="150" placeholder="Accompagnateur" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg">
            <select name="status" class="md:col-span-1 px-4 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="active">Actif</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactif</option>
            </select>
            <div class="md:col-span-6">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Ajouter</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Immatriculation</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600">Capacité</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Conducteur</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Accompagnateur</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($vehicles as $vehicle)
                <tr x-show="editing !== {{ $vehicle->id }}">
                    <td class="py-2 px-3 font-mono font-semibold">{{ $vehicle->plate_number }}</td>
                    <td class="py-2 px-3 text-center">{{ $vehicle->occupiedSeatsCount() }}/{{ $vehicle->capacity }}</td>
                    <td class="py-2 px-3">{{ $vehicle->driver_name }} <span class="text-gray-400">{{ $vehicle->driver_phone }}</span></td>
                    <td class="py-2 px-3">{{ $vehicle->assistant_name }}</td>
                    <td class="py-2 px-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $vehicle->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($vehicle->status) }}</span>
                    </td>
                    <td class="py-2 px-3 text-center">
                        <button @click="editing = {{ $vehicle->id }}" class="text-primary hover:text-primary-dark mr-2">✏️</button>
                        <form action="{{ route('extras.transport.vehicles.destroy', $vehicle->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce véhicule ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                        </form>
                    </td>
                </tr>
                <tr x-show="editing === {{ $vehicle->id }}" class="bg-blue-50">
                    <td colspan="6" class="py-3 px-4">
                        <form action="{{ route('extras.transport.vehicles.update', $vehicle->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            @csrf @method('PUT')
                            <input type="text" name="plate_number" required value="{{ $vehicle->plate_number }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="number" name="capacity" required min="1" value="{{ $vehicle->capacity }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="driver_name" value="{{ $vehicle->driver_name }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="driver_phone" value="{{ $vehicle->driver_phone }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="assistant_name" value="{{ $vehicle->assistant_name }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white">
                                <option value="active" {{ $vehicle->status === 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="maintenance" {{ $vehicle->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="inactive" {{ $vehicle->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            </select>
                            <div class="md:col-span-6 flex gap-2">
                                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-semibold">Enregistrer</button>
                                <button type="button" @click="editing = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Annuler</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-8 text-center text-gray-500">Aucun véhicule enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
