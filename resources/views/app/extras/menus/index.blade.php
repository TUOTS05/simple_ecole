@extends('layouts.app')

@section('title', 'Menus Cantine')
@section('page_title', 'Menus')

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
        <form method="GET" class="flex flex-wrap gap-4 items-end mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Extra (cantine) *</label>
                <select name="extra_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($extras as $extra)
                    <option value="{{ $extra->id }}" {{ $extraId == $extra->id ? 'selected' : '' }}>{{ $extra->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </form>

        @if($extraId)
        <h4 class="font-semibold text-gray-700 mb-3">+ Ajouter / modifier un menu</h4>
        <form action="{{ route('extras.menus.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-8">
            @csrf
            <input type="hidden" name="extra_id" value="{{ $extraId }}">
            <input type="date" name="date" required class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="entree" placeholder="Entrée" maxlength="150" class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="plat" placeholder="Plat" maxlength="150" class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="dessert" placeholder="Dessert" maxlength="150" class="px-3 py-2 border border-gray-300 rounded-lg">
            <input type="text" name="gouter" placeholder="Goûter" maxlength="150" class="px-3 py-2 border border-gray-300 rounded-lg">
            <div class="md:col-span-5 flex justify-end">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Enregistrer</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Date</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Entrée</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Plat</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Dessert</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Goûter</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($menus as $menu)
                    <tr>
                        <td class="py-2 px-3 font-medium">{{ $menu->date->format('d/m/Y') }}</td>
                        <td class="py-2 px-3">{{ $menu->entree }}</td>
                        <td class="py-2 px-3">{{ $menu->plat }}</td>
                        <td class="py-2 px-3">{{ $menu->dessert }}</td>
                        <td class="py-2 px-3">{{ $menu->gouter }}</td>
                        <td class="py-2 px-3 text-center">
                            <form action="{{ route('extras.menus.destroy', $menu->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce menu ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Aucun menu pour ce mois.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
