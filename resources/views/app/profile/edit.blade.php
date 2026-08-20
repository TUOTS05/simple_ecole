@extends('layouts.app')

@section('title', 'Profil & Établissement - Administration')
@section('page_title', 'Profil & Établissement')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête -->
    <div class="flex items-center mb-6">
        <a href="{{ route('app.dashboard') }}" class="mr-4 text-gray-500 hover:text-primary transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Profil et Établissement</h1>
            <p class="text-sm text-gray-500">Gérez vos informations personnelles et celles de votre école</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('success_password'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success_password') }}</span>
        </div>
    @endif

    <form action="{{ route('app.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- ========================================== -->
        <!-- SECTION 1 : INFORMATIONS DE L'ADMINISTRATEUR -->
        <!-- ========================================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Informations de l'Administrateur
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prénom</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('first_name') border-red-500 @enderror">
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('last_name') border-red-500 @enderror">
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email personnel</label>
                    <input type="email" name="user_email" value="{{ old('user_email', $user->email) }}" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('user_email') border-red-500 @enderror">
                    @error('user_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone personnel</label>
                    <input type="text" name="user_phone" value="{{ old('user_phone', $user->phone ?? '') }}" 
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('user_phone') border-red-500 @enderror">
                    @error('user_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION 2 : INFORMATIONS DE L'ÉTABLISSEMENT -->
        <!-- ========================================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Informations de l'Établissement
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Logo de l'école</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                            @if($school->logo ?? null)
                                <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo actuel" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            @endif
                        </div>
                        <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg"
                               class="flex-1 text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold hover:file:bg-primary/20 @error('logo') border-red-500 @enderror">
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">PNG ou JPG, 2 Mo maximum. Utilisé sur les bulletins, reçus et rapports exportés.</p>
                    @error('logo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom de l'école *</label>
                    <input type="text" name="school_name" value="{{ old('school_name', $school->name ?? '') }}" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('school_name') border-red-500 @enderror">
                    @error('school_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Type d'école *</label>
                    <select name="school_type" required class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('school_type') border-red-500 @enderror">
                        <option value="maternelle" {{ old('school_type', $school->school_type ?? '') == 'maternelle' ? 'selected' : '' }}>Maternelle</option>
                        <option value="primaire" {{ old('school_type', $school->school_type ?? '') == 'primaire' ? 'selected' : '' }}>Primaire</option>
                        <option value="both" {{ old('school_type', $school->school_type ?? '') == 'both' ? 'selected' : '' }}>Maternelle & Primaire</option>
                    </select>
                    @error('school_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email de l'école</label>
                    <input type="email" name="school_email" value="{{ old('school_email', $school->email ?? '') }}" 
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('school_email') border-red-500 @enderror">
                    @error('school_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone de l'école</label>
                    <input type="text" name="school_phone" value="{{ old('school_phone', $school->phone ?? '') }}" 
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('school_phone') border-red-500 @enderror">
                    @error('school_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse de l'établissement</label>
                    <textarea name="school_address" rows="3" 
                              class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('school_address') border-red-500 @enderror"
                              placeholder="Adresse postale complète...">{{ old('school_address', $school->address ?? '') }}</textarea>
                    @error('school_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Bouton d'action global -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-8 rounded-lg transition shadow-md flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Enregistrer toutes les modifications
            </button>
        </div>
    </form>

    <!-- ========================================== -->
    <!-- ✅ NOUVELLE SECTION 3 : CHANGEMENT DE MOT DE PASSE -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Changer le mot de passe
            </h2>
        </div>
        <div class="p-6">
            <form action="{{ route('app.profile.password.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mot de passe actuel *</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('current_password') border-red-500 @enderror"
                           placeholder="••••••••">
                    @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nouveau mot de passe *</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('password') border-red-500 @enderror"
                               placeholder="Minimum 6 caractères">
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmer le nouveau mot de passe *</label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                               placeholder="Répétez le nouveau mot de passe">
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-3 px-8 rounded-lg transition shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Mettre à jour le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection