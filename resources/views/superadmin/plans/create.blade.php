@extends('layouts.app')

@section('title', 'Nouveau Plan')
@section('page_title', 'Créer un nouveau plan d\'abonnement')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
                        @csrf
            
            <!-- ✅ AJOUTEZ CE BLOC POUR VOIR LES ERREURS CACHÉES -->
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p class="font-bold">Oups ! Quelques erreurs ont été rencontrées :</p>
                    <ul class="list-disc list-inside mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('superadmin.plans.store') }}" method="POST">
                @csrf
                
                <div class="space-y-8">
                    
                    <!-- SECTION 1 : INFORMATIONS GÉNÉRALES -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">📋</span> Informations générales
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nom -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du plan *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Slug -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Slug (identifiant unique) *</label>
                                <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Généré automatiquement si vide"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('slug') border-red-500 @enderror">
                                @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                <p class="text-xs text-gray-500 mt-1">Ex: basic, premium, enterprise</p>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2 : LIMITES DU PLAN -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">📊</span> Limites du plan
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Max élèves -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max. élèves *</label>
                                <input type="number" name="max_students" value="{{ old('max_students', 100) }}" required min="1"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_students') border-red-500 @enderror">
                                @error('max_students') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Max enseignants -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max. enseignants *</label>
                                <input type="number" name="max_teachers" value="{{ old('max_teachers', 10) }}" required min="1"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_teachers') border-red-500 @enderror">
                                @error('max_teachers') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Max classes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max. classes *</label>
                                <input type="number" name="max_classes" value="{{ old('max_classes', 20) }}" required min="1"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_classes') border-red-500 @enderror">
                                @error('max_classes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3 : TARIFICATION -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">💰</span> Tarification (FCFA)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Prix mensuel -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prix mensuel *</label>
                                <input type="number" name="monthly_price" value="{{ old('monthly_price', 0) }}" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('monthly_price') border-red-500 @enderror">
                                @error('monthly_price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Prix annuel -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prix annuel *</label>
                                <input type="number" name="yearly_price" value="{{ old('yearly_price', 0) }}" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('yearly_price') border-red-500 @enderror">
                                @error('yearly_price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4 : ÉTAT -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <span class="mr-2">⚙️</span> État et affichage
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Actif -->
                            <div>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Plan actif (visible pour les écoles)</span>
                                </label>
                            </div>

                            <!-- Ordre d'affichage -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ordre d'affichage</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('sort_order') border-red-500 @enderror">
                                @error('sort_order') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                <p class="text-xs text-gray-500 mt-1">Plus le nombre est petit, plus le plan apparaît en premier</p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Boutons -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-4">
                    <a href="{{ route('superadmin.plans.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition shadow-md">
                        💾 Créer le plan
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
@endsection