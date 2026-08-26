@extends('layouts.app')

@section('title', 'Nouvelle École')
@section('page_title', 'Ajouter une nouvelle école')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">

        <form action="{{ route('superadmin.schools.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-8">

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 1 : INFORMATIONS DE L'ÉTABLISSEMENT -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">🏫</span> Informations de l'établissement
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nom -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'école *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required oninput="generateSlug()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" readonly
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Généré automatiquement à partir du nom.</p>
                        </div>

                        <!-- Type d'école -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type d'école *</label>
                            <select name="school_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('school_type') border-red-500 @enderror">
                                <option value="">-- Sélectionner --</option>
                                <option value="maternelle" {{ old('school_type') === 'maternelle' ? 'selected' : '' }}>Maternelle uniquement</option>
                                <option value="primaire" {{ old('school_type') === 'primaire' ? 'selected' : '' }}>Primaire uniquement</option>
                                <option value="both" {{ old('school_type') === 'both' ? 'selected' : '' }}>Maternelle + Primaire</option>
                            </select>
                            @error('school_type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Adresse -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('address') border-red-500 @enderror">
                            @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('phone') border-red-500 @enderror">
                            @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo de l'école</label>
                            <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('logo') border-red-500 @enderror">
                            @error('logo') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 mt-1">Max 2MB, formats: JPG, PNG</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 2 : COMPTE DU DIRECTEUR/ADMIN -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">👤</span> Compte du directeur
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nom complet -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
                            <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('admin_name') border-red-500 @enderror">
                            @error('admin_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (servira de login) *</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('admin_email') border-red-500 @enderror">
                            @error('admin_email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe *</label>
                            <input type="password" name="admin_password" required minlength="8"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('admin_password') border-red-500 @enderror">
                            @error('admin_password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 mt-1">8 caractères minimum</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- SECTION 3 : STATUT INITIAL -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                        <span class="mr-2">⚙️</span> Statut initial
                    </h3>
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                        <input type="hidden" name="status" value="suspended">
                        <div class="flex items-center text-yellow-800 font-semibold">
                            <span class="mr-2 text-xl">⏳</span> En attente d'abonnement
                        </div>
                        <p class="text-xs text-gray-600 mt-2">
                            ️ L'école est créée avec le statut <strong>"Suspendu"</strong>. 
                            Elle sera activée automatiquement lors de la souscription à un abonnement sur la page <strong>Abonnements</strong>.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Boutons d'action -->
            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-4">
                <a href="{{ route('superadmin.schools.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                    Annuler
                </a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition shadow-md flex items-center">
                    <span class="mr-2">💾</span> Créer l'école et son directeur
                </button>
            </div>

        </form>

    </div>
</div>

<!-- Script pour générer le slug automatiquement -->
<script>
    function generateSlug() {
        const name = document.getElementById('name').value;
        const slugInput = document.getElementById('slug');
        
        const slug = name.toLowerCase()
                         .replace(/[^a-z0-9\s-]/g, '')
                         .replace(/\s+/g, '-')
                         .replace(/-+/g, '-');
        
        slugInput.value = slug;
    }
</script>
@endsection