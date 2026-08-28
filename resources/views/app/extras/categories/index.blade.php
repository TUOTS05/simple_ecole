@extends('layouts.app')

@section('title', 'Catégories d\'Extras')
@section('page_title', 'Catégories d\'Extras')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ editing: null }">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">+ Nouvelle Catégorie</h3>
        <form action="{{ route('extras.categories.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            @csrf
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Icône</label>
                <input type="text" name="icon" maxlength="10" placeholder="🚌" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ordre</label>
                <input type="number" name="order" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Ajouter</button>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" maxlength="500" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Catégorie</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Description</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Extras</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50 transition" x-show="editing !== {{ $category->id }}">
                    <td class="py-3 px-4 font-medium text-gray-800">
                        <span class="text-lg mr-1">{{ $category->icon }}</span> {{ $category->name }}
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $category->description }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ $category->extras_count }}</td>
                    <td class="py-3 px-4 text-center">
                        <button @click="editing = {{ $category->id }}" class="text-primary hover:text-primary-dark mr-3">✏️ Modifier</button>
                        <form action="{{ route('extras.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
                <tr x-show="editing === {{ $category->id }}" class="bg-blue-50">
                    <td colspan="4" class="py-3 px-4">
                        <form action="{{ route('extras.categories.update', $category->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                            @csrf @method('PUT')
                            <input type="text" name="icon" maxlength="10" value="{{ $category->icon }}" class="md:col-span-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="name" required value="{{ $category->name }}" class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="text" name="description" maxlength="500" value="{{ $category->description }}" class="md:col-span-2 px-3 py-2 border border-gray-300 rounded-lg">
                            <input type="number" name="order" min="0" value="{{ $category->order }}" class="md:col-span-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <div class="md:col-span-6 flex gap-2">
                                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-semibold">Enregistrer</button>
                                <button type="button" @click="editing = null" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Annuler</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-12 text-center text-gray-500">Aucune catégorie créée pour le moment.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
