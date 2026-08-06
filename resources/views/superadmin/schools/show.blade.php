@extends('layouts.app')

@section('title', $school->name)
@section('page_title', $school->name)

@section('content')
    
    <div class="max-w-4xl mx-auto">
        
        <!-- Bouton retour -->
        <div class="mb-6">
            <a href="{{ route('superadmin.schools.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour à la liste
            </a>
        </div>
        
        <!-- Carte principale -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            
            <div class="flex items-start space-x-6">
                
                <!-- Logo -->
                <div>
                    @if($school->logo)
                        <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}" class="w-32 h-32 rounded-lg object-cover">
                    @else
                        <div class="w-32 h-32 rounded-lg bg-gray-200 flex items-center justify-center text-5xl text-gray-500">
                            🏫
                        </div>
                    @endif
                </div>
                
                <!-- Infos -->
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $school->name }}</h1>
                    <p class="text-gray-600 mb-4">URL: <span class="font-mono text-sm">{{ $school->slug }}</span></p>
                    
                    <div class="flex space-x-4">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold bg-primary text-white">
                            Type: {{ $school->school_type }}
                        </span>
                        <span class="px-4 py-2 rounded-full text-sm font-semibold 
                            {{ $school->status === 'active' ? 'bg-accent text-white' : ($school->status === 'trial' ? 'bg-secondary text-gray-800' : 'bg-danger text-white') }}">
                            Statut: {{ $school->status }}
                        </span>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-primary">
                <p class="text-sm text-gray-600 mb-1">Utilisateurs</p>
                <p class="text-3xl font-bold text-gray-800">{{ $school->users_count }}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-accent">
                <p class="text-sm text-gray-600 mb-1">Élèves</p>
                <p class="text-3xl font-bold text-gray-800">{{ $school->students_count }}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-secondary">
                <p class="text-sm text-gray-600 mb-1">Classes</p>
                <p class="text-3xl font-bold text-gray-800">{{ $school->classes_count }}</p>
            </div>
            
        </div>
        
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            
            <div class="flex space-x-4">
                <a href="{{ route('superadmin.schools.edit', $school) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    ✏️ Modifier
                </a>
                
                <form action="{{ route('superadmin.schools.destroy', $school) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette école ?')">
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