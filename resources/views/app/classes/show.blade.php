@extends('layouts.app')

@section('title', $class->name)
@section('page_title', 'Classe ' . $class->name)

@section('content')
    
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.classes.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour à la liste
            </a>
        </div>
        
        <!-- Carte principale -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $class->name }}</h1>
                    <div class="flex space-x-4">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $class->cycle === 'maternelle' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $class->cycle === 'maternelle' ? '🧒 Maternelle' : '📚 Primaire' }}
                        </span>
                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">
                            📊 Niveau : {{ $class->level }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Élèves inscrits</p>
                    <p class="text-4xl font-bold text-primary">{{ $class->students_count }}</p>
                    @if($class->capacity)
                        <p class="text-sm text-gray-500">/ {{ $class->capacity }} places</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        @if($class->capacity)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-primary">
                    <p class="text-sm text-gray-600 mb-1">Places disponibles</p>
                    <p class="text-3xl font-bold text-gray-800">
                        {{ max(0, $class->capacity - $class->students_count) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-accent">
                    <p class="text-sm text-gray-600 mb-1">Taux de remplissage</p>
                    <p class="text-3xl font-bold text-gray-800">
                        {{ round(($class->students_count / $class->capacity) * 100) }}%
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-secondary">
                    <p class="text-sm text-gray-600 mb-1">Capacité totale</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $class->capacity }}</p>
                </div>
            </div>
        @endif
        
        <!-- Liste des élèves -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                👨‍🎓 Élèves de la classe ({{ $students->count() }})
            </h2>
            
            @if($students->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matricule</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom complet</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Genre</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                        {{ $student->matricule }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold">
                                    {{ $student->last_name }} {{ $student->first_name }}
                                </td>
                                <td class="py-3 px-4">
                                    {{ $student->gender === 'M' ? '👦 Garçon' : '👧 Fille' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        {{ $student->status === 'active' ? 'bg-accent text-white' : 'bg-gray-300 text-gray-700' }}">
                                        {{ $student->status === 'active' ? '✅ Actif' : '⏸️ Inactif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('app.students.show', $student) }}" class="text-blue-600 hover:text-blue-800">
                                            👁️ Voir
                                        </a>
                                        <a href="{{ route('app.students.dossier', $student) }}" class="text-purple-600 hover:text-purple-800">
                                            📁 Dossier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📭</div>
                    <p class="text-gray-500 text-lg">Aucun élève dans cette classe</p>
                    <a href="{{ route('app.students.create') }}" class="inline-block mt-4 bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                        + Ajouter un élève
                    </a>
                </div>
            @endif
        </div>
        
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            <div class="flex space-x-4">
                <a href="{{ route('app.classes.edit', $class) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    ✏️ Modifier
                </a>
                <form action="{{ route('app.classes.destroy', $class) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                        🗑️ Supprimer
                    </button>
                </form>
            </div>
        </div>
        
    </div>
    
@endsection