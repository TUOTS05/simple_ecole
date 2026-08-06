@extends('layouts.app')

@section('title', $user->first_name . ' ' . $user->last_name)
@section('page_title', 'Détails Utilisateur')

@section('content')
    
    <div class="max-w-4xl mx-auto">
        
        <!-- Retour -->
        <div class="mb-6">
            <a href="{{ route('superadmin.users.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour à la liste
            </a>
        </div>
        
        <!-- Carte principale -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            
            <div class="flex items-start space-x-6">
                
                <!-- Avatar -->
                <div>
                    <div class="w-32 h-32 rounded-full bg-primary flex items-center justify-center text-white text-5xl font-bold">
                        {{ strtoupper(substr($user->first_name, 0, 1)) }}
                    </div>
                </div>
                
                <!-- Infos -->
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        {{ $user->first_name }} {{ $user->last_name }}
                    </h1>
                    <p class="text-gray-600 mb-4">{{ $user->email }}</p>
                    
                    <div class="flex space-x-4">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $user->role === 'super_admin' ? 'bg-danger text-white' : 
                               ($user->role === 'school_admin' ? 'bg-secondary text-gray-800' : 
                               ($user->role === 'teacher' ? 'bg-primary text-white' : 'bg-accent text-white')) }}">
                            @if($user->role === 'super_admin') Super Admin
                            @elseif($user->role === 'school_admin') Admin École
                            @elseif($user->role === 'teacher') Enseignant
                            @else Parent
                            @endif
                        </span>
                        
                        @if($user->school)
                            <span class="px-4 py-2 rounded-full text-sm font-semibold bg-gray-200 text-gray-800">
                                🏫 {{ $user->school->name }}
                            </span>
                        @endif
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Détails -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Informations personnelles</h2>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-600">Prénom</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->first_name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">Nom</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->last_name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">Email</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->email }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">Téléphone</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Informations système</h2>
                
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-gray-600">Rôle</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->role }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">École</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->school->name ?? '—' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">Créé le</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm text-gray-600">Dernière modification</dt>
                        <dd class="text-lg font-semibold text-gray-800">{{ $user->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
            
        </div>
        
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            
            <div class="flex space-x-4">
                <a href="{{ route('superadmin.users.edit', $user) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    ✏️ Modifier
                </a>
                
                <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
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