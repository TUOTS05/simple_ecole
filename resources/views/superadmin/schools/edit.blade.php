@extends('layouts.app')

@section('title', 'Modifier l\'école')
@section('page_title', 'Modifier : ' . $school->name)

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            
            <form action="{{ route('superadmin.schools.update', $school) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="space-y-8">
                    
                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <!-- SECTION 1 : INFORMATIONS DE BASE -->
                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">🏫</span> Informations de l'établissement
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nom -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'école *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $school->name) }}" required oninput="generateSlug()"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <!-- Slug -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL) *</label>
                                <input type="text" name="slug" id="slug" value="{{ old('slug', $school->slug) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('slug') border-red-500 @enderror">
                                @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Type d'école -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'école *</label>
                                <select name="school_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('school_type') border-red-500 @enderror">
                                    <option value="maternelle" {{ old('school_type', $school->school_type) === 'maternelle' ? 'selected' : '' }}>Maternelle uniquement</option>
                                    <option value="primaire" {{ old('school_type', $school->school_type) === 'primaire' ? 'selected' : '' }}>Primaire uniquement</option>
                                    <option value="both" {{ old('school_type', $school->school_type) === 'both' ? 'selected' : '' }}>Maternelle + Primaire</option>
                                </select>
                                @error('school_type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" >Email de contact *</label>
                                <input type="email" name="email" value="{{ old('email', $school->email) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('email') border-red-500 @enderror">
                                @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Téléphone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <input type="tel" name="phone" value="{{ old('phone', $school->phone) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone') border-red-500 @enderror">
                                @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Adresse -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                                <textarea name="address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('address') border-red-500 @enderror">{{ old('address', $school->address) }}</textarea>
                                @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <!-- SECTION 2 : CONTRAT ET ABONNEMENT (SAAS) -->
                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">📜</span> Contrat et Abonnement
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Plan d'abonnement -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Plan d'abonnement *</label>
                                <select name="subscription_plan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('subscription_plan') border-red-500 @enderror">
                                    <option value="basic" {{ old('subscription_plan', $school->subscription_plan) === 'basic' ? 'selected' : '' }}>Basique (Limité)</option>
                                    <option value="premium" {{ old('subscription_plan', $school->subscription_plan) === 'premium' ? 'selected' : '' }}>Premium (Recommandé)</option>
                                    <option value="enterprise" {{ old('subscription_plan', $school->subscription_plan) === 'enterprise' ? 'selected' : '' }}>Entreprise (Illimité)</option>
                                </select>
                                @error('subscription_plan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Nombre max d'élèves -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre max. d'élèves *</label>
                                <input type="number" name="max_students" value="{{ old('max_students', $school->max_students) }}" required min="1"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_students') border-red-500 @enderror">
                                @error('max_students') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Date de début -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de début d'abonnement *</label>
                                <input type="date" name="subscription_start_date" value="{{ old('subscription_start_date', $school->subscription_start_date?->format('Y-m-d')) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('subscription_start_date') border-red-500 @enderror">
                                @error('subscription_start_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Date de fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date d'expiration *</label>
                                <input type="date" name="subscription_end_date" value="{{ old('subscription_end_date', $school->subscription_end_date?->format('Y-m-d')) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('subscription_end_date') border-red-500 @enderror">
                                <p class="text-xs text-red-500 mt-1">⚠️ L'école sera automatiquement bloquée après cette date.</p>
                                @error('subscription_end_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <!-- SECTION 3 : ÉTAT ET APPARENCE -->
                    <!-- ═══════════════════════════════════════════════════════════════ -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">⚙️</span> État et Apparence
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Statut -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Statut du compte *</label>
                                <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                                    <option value="active" {{ old('status', $school->status) === 'active' ? 'selected' : '' }}>🟢 Actif (Fonctionnement normal)</option>
                                    <option value="suspended" {{ old('status', $school->status) === 'suspended' ? 'selected' : '' }}>🟡 Suspendu (Accès bloqué manuellement)</option>
                                    <option value="expired" {{ old('status', $school->status) === 'expired' ? 'selected' : '' }}>🔴 Expiré (Fin de contrat)</option>
                                </select>
                                @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Logo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo de l'école</label>
                                @if($school->logo)
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo actuel" class="w-20 h-20 rounded-lg object-cover border border-gray-200">
                                        <p class="text-xs text-gray-500 mt-1">Logo actuel</p>
                                    </div>
                                @endif
                                <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('logo') border-red-500 @enderror">
                                @error('logo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                <p class="text-xs text-gray-500 mt-1">Laissez vide pour conserver le logo actuel. Max 2MB.</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Boutons d'action -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-4">
                    <a href="{{ route('superadmin.schools.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition shadow-md">
                        💾 Mettre à jour l'école
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>

    <!-- Script pour mettre à jour le slug automatiquement (optionnel mais pratique) -->
    <script>
        function generateSlug() {
            const name = document.getElementById('name').value;
            const slugInput = document.getElementById('slug');
            
            const slug = name.toLowerCase()
                             .replace(/[^a-z0-9\s-]/g, '')
                             .replace(/\s+/g, '-')
                             .replace(/-+/g, '-');
            
            // On ne met à jour que si l'utilisateur n'a pas déjà modifié manuellement le slug
            if (slugInput.value === '' || slugInput.dataset.autoGenerated === 'true') {
                slugInput.value = slug;
                slugInput.dataset.autoGenerated = 'true';
            }
        }
        
        // Initialiser l'état au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const slugInput = document.getElementById('slug');
            if (slugInput.value === '') {
                slugInput.dataset.autoGenerated = 'true';
            }
        });
    </script>
@endsection