@extends('layouts.app')

@section('title', 'Classes')
@section('page_title', 'Classes')

@section('content')
    
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
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Classes</h1>
            <p class="text-gray-600 mt-1">Gérez les classes de votre école</p>
        </div>
        <a href="{{ route('app.classes.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouvelle Classe
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('app.classes.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nom de la classe..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cycle</label>
                <select name="cycle" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les cycles</option>
                    <option value="maternelle" {{ request('cycle') === 'maternelle' ? 'selected' : '' }}>🧒 Maternelle</option>
                    <option value="primaire" {{ request('cycle') === 'primaire' ? 'selected' : '' }}>📚 Primaire</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('app.classes.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                    Réinitialiser
                </a>
            </div>
            
        </form>
    </div>
    
    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Cycle</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Niveau</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élèves</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Capacité</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Taux</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">{{ $class->name }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $class->cycle === 'maternelle' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $class->cycle === 'maternelle' ? '🧒 Maternelle' : '📚 Primaire' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                {{ $class->level }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-bold">{{ $class->students_count }}</td>
                        <td class="py-3 px-4">{{ $class->capacity ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @if($class->capacity)
                                @php
                                    $taux = ($class->students_count / $class->capacity) * 100;
                                @endphp
                                <div class="flex items-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="h-2 rounded-full {{ $taux > 90 ? 'bg-danger' : ($taux > 70 ? 'bg-secondary' : 'bg-accent') }}" 
                                             style="width: {{ min($taux, 100) }}%"></div>
                                    </div>
                                    <span class="text-sm">{{ round($taux) }}%</span>
                                </div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.classes.show', $class) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <a href="{{ route('app.classes.edit', $class) }}" class="text-yellow-600 hover:text-yellow-800">
                                    ✏️
                                </a>
                                <form action="{{ route('app.classes.destroy', $class) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr ?')">
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
                        <td colspan="7" class="py-8 text-center text-gray-500">
                            Aucune classe trouvée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($classes->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $classes->links() }}
            </div>
        @endif
    </div>
    
@endsection