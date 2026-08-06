@extends('layouts.app')

@section('title', 'Modifier l\'utilisateur')
@section('page_title', 'Modifier l\'utilisateur')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('superadmin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    
                    <!-- Nom et Prénom -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('first_name') border-red-500 @enderror">
                            @error('first_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('last_name') border-red-500 @enderror">
                            @error('last_name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Téléphone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Rôle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rôle *</label>
                        <select name="role" id="role" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('role') border-red-500 @enderror">
                            <option value="">Sélectionner un rôle</option>
                            <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="school_admin" {{ old('role', $user->role) === 'school_admin' ? 'selected' : '' }}>Admin École</option>
                            <option value="teacher" {{ old('role', $user->role) === 'teacher' ? 'selected' : '' }}>Enseignant</option>
                            <option value="parent" {{ old('role', $user->role) === 'parent' ? 'selected' : '' }}>Parent</option>
                        </select>
                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- École -->
                    <div id="school-field">
                        <label class="block text-sm font-medium text-gray-700 mb-2">École *</label>
                        <select name="school_id" id="school_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('school_id') border-red-500 @enderror">
                            <option value="">Sélectionner une école</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id', $user->school_id) == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Non requis pour Super Admin</p>
                    </div>
                    
                    <!-- Nouveau mot de passe (optionnel) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                        <input type="password" name="password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Laisser vide pour conserver le mot de passe actuel</p>
                    </div>
                    
                    <!-- Confirmation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="password_confirmation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                </div>
                
                <!-- Boutons -->
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Mettre à jour
                    </button>
                    <a href="{{ route('superadmin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <script>
        // Afficher/masquer le champ école selon le rôle
        document.getElementById('role').addEventListener('change', function() {
            const schoolField = document.getElementById('school-field');
            const schoolSelect = document.getElementById('school_id');
            
            if (this.value === 'super_admin') {
                schoolField.style.display = 'none';
                schoolSelect.required = false;
                schoolSelect.value = '';
            } else {
                schoolField.style.display = 'block';
                schoolSelect.required = true;
            }
        });
        
        // Initialiser au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const role = document.getElementById('role');
            if (role.value) {
                role.dispatchEvent(new Event('change'));
            }
        });
    </script>
    
@endsection