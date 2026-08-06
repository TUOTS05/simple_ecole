@extends('layouts.app')

@section('title', 'Gestion des Utilisateurs')
@section('page_title', 'Utilisateurs')

@section('content')
    
    <!-- Messages -->
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
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Utilisateurs</h1>
            <p class="text-gray-600 mt-1">Gérez tous les comptes de la plateforme</p>
        </div>
        <a href="{{ route('superadmin.users.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouvel Utilisateur
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('superadmin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nom, prénom ou email..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les rôles</option>
                    <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="school_admin" {{ request('role') === 'school_admin' ? 'selected' : '' }}>Admin École</option>
                    <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Enseignant</option>
                    <option value="parent" {{ request('role') === 'parent' ? 'selected' : '' }}>Parent</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">École</label>
                <select name="school_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les écoles</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('superadmin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
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
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom complet</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rôle</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">École</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Créé le</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $user->role === 'super_admin' ? 'bg-danger text-white' : 
                                   ($user->role === 'school_admin' ? 'bg-secondary text-gray-800' : 
                                   ($user->role === 'teacher' ? 'bg-primary text-white' : 'bg-accent text-white')) }}">
                                @if($user->role === 'super_admin') Super Admin
                                @elseif($user->role === 'school_admin') Admin École
                                @elseif($user->role === 'teacher') Enseignant
                                @else Parent
                                @endif
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm">
                            {{ $user->school->name ?? '—' }}
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('superadmin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <a href="{{ route('superadmin.users.edit', $user) }}" class="text-yellow-600 hover:text-yellow-800">
                                    ✏️
                                </a>
                                <form action="{{ route('superadmin.users.destroy', $user) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
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
                            Aucun utilisateur trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
    
@endsection