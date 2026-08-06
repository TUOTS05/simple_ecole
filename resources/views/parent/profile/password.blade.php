@extends('layouts.app')

@section('title', 'Modifier mon mot de passe')
@section('page_title', 'Sécurité du compte')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête avec retour -->
    <div class="flex items-center mb-6">
        <a href="{{ route('parent.dashboard') }}" class="mr-4 text-gray-500 hover:text-primary transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Modifier mon mot de passe</h1>
            <p class="text-sm text-gray-500">Mettez à jour vos identifiants pour sécuriser votre compte</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 sm:p-8">
            <!-- Message de succès -->
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('parent.profile.password.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Mot de passe actuel -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mot de passe actuel *</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('current_password') border-red-500 @enderror"
                           placeholder="••••••••">
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Nouveau mot de passe -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nouveau mot de passe *</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('password') border-red-500 @enderror"
                           placeholder="Minimum 6 caractères">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1.5 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Confirmation du nouveau mot de passe -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirmer le nouveau mot de passe *</label>
                    <input type="password" name="password_confirmation" required minlength="6"
                           class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                           placeholder="Répétez le nouveau mot de passe">
                </div>

                <!-- Bouton d'action -->
                <div class="pt-4">
                    <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-8 rounded-lg transition shadow-md flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Mettre à jour mon mot de passe
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Info sécurité -->
        <div class="bg-blue-50 border-t border-blue-100 p-6">
            <p class="text-sm text-blue-800 flex items-start">
                <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Pour des raisons de sécurité, vous serez déconnecté de tous vos autres appareils après cette modification.</span>
            </p>
        </div>
    </div>
</div>
@endsection