@extends('layouts.app')

@section('title', 'Affectations Transport')
@section('page_title', 'Affectation des élèves aux circuits')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ routeStops: {} }">

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
    @if($routes->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 text-center text-gray-500">
        Aucun circuit défini pour cet extra. <a href="{{ route('extras.transport.routes.index', ['extra_id' => $extraId]) }}" class="text-primary underline">En créer un</a>.
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Circuit actuel</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Affecter à</th>
                    <th class="text-center py-2 px-3 font-semibold text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $sub)
                <tr>
                    <td class="py-2 px-3 font-medium">{{ $sub->student->last_name }} {{ $sub->student->first_name }}</td>
                    <td class="py-2 px-3 text-gray-600">
                        @if($sub->transportAssignment)
                        {{ $sub->transportAssignment->route->name }} — {{ $sub->transportAssignment->stop->label ?? 'N/A' }}
                        <form action="{{ route('extras.transport.assignments.destroy', $sub->transportAssignment->id) }}" method="POST" class="inline" onsubmit="return confirm('Retirer cette affectation ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs ml-2">🗑️</button>
                        </form>
                        @else
                        <span class="text-gray-400">Non affecté</span>
                        @endif
                    </td>
                    <td class="py-2 px-3">
                        <form action="{{ route('extras.transport.assignments.store') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="hidden" name="extra_subscription_id" value="{{ $sub->id }}">
                            <select name="extra_route_id" required onchange="routeStops = { ...routeStops, {{ $sub->id }}: this.value }" class="px-2 py-1 border border-gray-300 rounded bg-white text-xs">
                                <option value="">Circuit...</option>
                                @foreach($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </select>
                            <select name="extra_route_stop_id" class="px-2 py-1 border border-gray-300 rounded bg-white text-xs">
                                <option value="">Arrêt...</option>
                                @foreach($routes as $route)
                                    @foreach($route->stops as $stop)
                                    <option value="{{ $stop->id }}">{{ $route->name }} — {{ $stop->label }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <button type="submit" class="bg-primary text-white px-3 py-1 rounded text-xs font-semibold">OK</button>
                        </form>
                    </td>
                    <td></td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-8 text-center text-gray-500">Aucun élève actif inscrit à cet extra.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
