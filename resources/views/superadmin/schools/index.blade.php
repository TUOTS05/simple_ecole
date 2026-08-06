@extends('layouts.app')

@section('title', 'Gestion des Écoles')
@section('page_title', 'Écoles')

@section('content')
    
    <!-- Messages de succès/erreur -->
    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif
    
    <!-- Header avec bouton créer -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Écoles</h1>
            <p class="text-gray-600 mt-1">Gérez toutes les écoles de la plateforme</p>
        </div>
        <a href="{{ route('superadmin.schools.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouvelle École
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('superadmin.schools.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nom de l'école..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Essai</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="school_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les types</option>
                    <option value="maternelle" {{ request('school_type') === 'maternelle' ? 'selected' : '' }}>Maternelle</option>
                    <option value="primaire" {{ request('school_type') === 'primaire' ? 'selected' : '' }}>Primaire</option>
                    <option value="both" {{ request('school_type') === 'both' ? 'selected' : '' }}>Les deux</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('superadmin.schools.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                    Réinitialiser
                </a>
            </div>
            
        </form>
    </div>
    
    <!-- Tableau des écoles -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Logo</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Créée le</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($school->logo)
                                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                    🏫
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-semibold">{{ $school->name }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary text-white">
                                {{ $school->school_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                {{ $school->status === 'active' ? 'bg-accent text-white' : ($school->status === 'trial' ? 'bg-secondary text-gray-800' : 'bg-danger text-white') }}">
                                {{ $school->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            {{ $school->created_at->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('superadmin.schools.show', $school) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <a href="{{ route('superadmin.schools.edit', $school) }}" class="text-yellow-600 hover:text-yellow-800">
                                    ✏️
                                </a>
                                <form action="{{ route('superadmin.schools.destroy', $school) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette école ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">
                            Aucune école trouvée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($schools->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $schools->links() }}
            </div>
        @endif
    </div>
    
@endsection