@extends('layouts.app')

@section('title', 'Stock Extras')
@section('page_title', 'Stock (Uniformes, Fournitures, Kits)')

@php
$typeLabels = ['in' => 'Entrée', 'out' => 'Sortie', 'sale' => 'Vente', 'return' => 'Retour'];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ editing: null }">

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
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Nouvel article</h3>
        <form action="{{ route('extras.stocks.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" name="name" required maxlength="150" placeholder="T-shirt sport taille M" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unité</label>
                <input type="text" name="unit" maxlength="30" placeholder="unité" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix unitaire *</label>
                <input type="number" name="unit_price" required min="0" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock initial</label>
                <input type="number" name="quantity_on_hand" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Seuil d'alerte</label>
                <input type="number" name="alert_threshold" min="0" placeholder="Aucun" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="md:col-span-6">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Ajouter l'article</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 pt-6"><h3 class="text-lg font-bold text-gray-800 mb-4">📦 Articles en stock</h3></div>
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Article</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Prix</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Stock disponible</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50 transition" x-show="editing !== {{ $item->id }}">
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ $item->name }}
                        @if($item->description)<div class="text-xs text-gray-400">{{ $item->description }}</div>@endif
                    </td>
                    <td class="py-3 px-4 text-right text-gray-800">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right {{ $item->isLowStock() ? 'text-red-600 font-bold' : 'text-gray-800' }}">
                        {{ $item->quantity_on_hand }} {{ $item->unit }}(s)
                        @if($item->isLowStock())<div class="text-xs">⚠️ Seuil d'alerte atteint</div>@endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $item->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $item->status === 'active' ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <button @click="editing = {{ $item->id }}" class="text-primary hover:text-primary-dark mr-3">✏️</button>
                        <form action="{{ route('extras.stocks.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cet article ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                        </form>
                    </td>
                </tr>
                <tr x-show="editing === {{ $item->id }}" class="bg-blue-50">
                    <td colspan="5" class="py-3 px-4">
                        <form action="{{ route('extras.stocks.update', $item->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            @csrf @method('PUT')
                            <input type="text" name="name" required value="{{ $item->name }}" class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="unit" value="{{ $item->unit }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="number" name="unit_price" required min="0" step="0.01" value="{{ $item->unit_price }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="number" name="alert_threshold" min="0" value="{{ $item->alert_threshold }}" placeholder="Seuil" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white">
                                <option value="active" {{ $item->status === 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ $item->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            </select>
                            <div class="md:col-span-6 flex gap-2">
                                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-semibold">Enregistrer</button>
                                <button type="button" @click="editing = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Annuler</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-12 text-center text-gray-500">Aucun article en stock.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6" x-data="{ type: 'sale' }">
        <h3 class="text-lg font-bold text-gray-800 mb-4">🔄 Enregistrer un mouvement</h3>
        <form action="{{ route('extras.stocks.movements.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Article *</label>
                <select name="extra_stock_item_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->quantity_on_hand }} {{ $item->unit }}(s))</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="type" x-model="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="sale">Vente</option>
                    <option value="in">Entrée (réappro)</option>
                    <option value="out">Sortie</option>
                    <option value="return">Retour</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                <input type="number" name="quantity" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div x-show="type === 'sale' || type === 'return'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève</label>
                <select name="student_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Aucun --</option>
                    @foreach($students as $student)
                    <option value="{{ $student->id }}">{{ $student->last_name }} {{ $student->first_name }} ({{ $student->matricule }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motif</label>
                <input type="text" name="reason" maxlength="255" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Historique des mouvements</h3>

        <form method="GET" class="flex flex-wrap gap-4 items-end mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Article</label>
                <select name="item_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Tous</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}" {{ $itemId == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Article</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Quantité</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Traité par</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $movement->processed_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">{{ $movement->item->name }}</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $typeLabels[$movement->type] ?? $movement->type }}</td>
                    <td class="py-3 px-4 text-right {{ in_array($movement->type, ['out', 'sale']) ? 'text-red-600' : 'text-green-600' }} font-semibold">
                        {{ in_array($movement->type, ['out', 'sale']) ? '-' : '+' }}{{ $movement->quantity }}
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $movement->student ? $movement->student->last_name.' '.$movement->student->first_name : '—' }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $movement->processedBy->full_name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-gray-500">Aucun mouvement enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4">{{ $movements->links() }}</div>
    </div>
</div>
@endsection
