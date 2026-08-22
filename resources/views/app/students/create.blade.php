@extends('layouts.app')

@section('title', 'Admission Élève')
@section('page_title', 'Nouvelle Admission')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <!-- En-tête -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Admission d'un Élève</h1>
            <p class="text-sm text-gray-500 mt-1">Enregistrez un nouvel élève dans l'établissement.</p>
        </div>
        <a href="{{ route('app.students.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour à la liste
        </a>
    </div>

    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('app.students.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <ul class="list-disc list-inside text-red-700 text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <!-- Section 1: Informations d'admission -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm font-bold">1</span>
                    Informations d'admission
                </h2>
                <button type="button" class="text-sm text-primary hover:text-primary-dark font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Importer un élève
                </button>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Numéro d'admission -->
                <!-- Numéro d'admission (Auto-généré) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Numéro Admission
                        <span class="text-xs text-gray-500 font-normal ml-1">(Auto-généré)</span>
                    </label>
                    <input type="text" name="admission_number" id="admission_number" value="{{ old('admission_number') }}" readonly
                        class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg cursor-not-allowed"
                        placeholder="Généré automatiquement">
                    <p class="text-xs text-gray-500 mt-1">Sera généré automatiquement</p>
                </div>

                <!-- Matricule (Auto-généré) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Matricule
                        <span class="text-xs text-gray-500 font-normal ml-1">(Auto-généré)</span>
                    </label>
                    <input type="text" name="matricule" value="{{ old('matricule') }}" readonly
                           class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg cursor-not-allowed"
                           placeholder="Généré automatiquement">
                    <p class="text-xs text-gray-500 mt-1">Sera généré automatiquement</p>
                </div>

                <!-- Classe -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Classe <span class="text-red-500">*</span>
                    </label>
                    <select name="class_id" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('class_id') border-red-500 @enderror">
                        <option value="">Sélectionner</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Section -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Section <span class="text-red-500">*</span>
                    </label>
                    <select name="section" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('section') border-red-500 @enderror">
                        <option value="">Sélectionner</option>
                        <option value="A" {{ old('section') == 'A' ? 'selected' : '' }}>Section A</option>
                        <option value="B" {{ old('section') == 'B' ? 'selected' : '' }}>Section B</option>
                        <option value="C" {{ old('section') == 'C' ? 'selected' : '' }}>Section C</option>
                    </select>
                    @error('section')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Prénom -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Prénom <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nom de famille -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nom de famille <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Genre -->
                <!-- Genre -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Genre <span class="text-red-500">*</span>
                    </label>
                    <select name="gender" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('gender') border-red-500 @enderror">
                        <option value="">Sélectionner</option>
                        <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                        <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date de naissance -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Date de naissance <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}" 
                    max="{{ date('Y-m-d') }}"
                    required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('birth_date') border-red-500 @enderror">
                    @error('birth_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Statut -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Statut <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="status" value="inactive">
                    <select name="status" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg cursor-not-allowed">
                        <option value="inactive" selected>Inactif (En attente de paiement)</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Famille nombreuse -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Appartenance à une famille nombreuse <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="large_family" value="1" {{ old('large_family') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">OUI</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="large_family" value="0" {{ old('large_family', 0) == '0' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">NON</span>
                        </label>
                    </div>
                </div>

                <!-- Enfant du personnel -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Enfant Du Personnel <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="staff_child" value="1" {{ old('staff_child') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">OUI</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="staff_child" value="0" {{ old('staff_child', 0) == '0' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">NON</span>
                        </label>
                    </div>
                </div>

                <!-- Religion -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Religion
                    </label>
                    <select name="religion"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                        <option value="">Sélectionner</option>
                        <option value="catholique" {{ old('religion') == 'catholique' ? 'selected' : '' }}>Catholique</option>
                        <option value="protestant" {{ old('religion') == 'protestant' ? 'selected' : '' }}>Protestant</option>
                        <option value="musulman" {{ old('religion') == 'musulman' ? 'selected' : '' }}>Musulman</option>
                        <option value="autre" {{ old('religion') == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <!-- Date d'admission -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Date d'admission
                    </label>
                    <input type="date" name="admission_date" value="{{ old('admission_date', date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <!-- Photo élève -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        élève Photo
                    </label>
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="file" name="student_photo" accept="image/*"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                        </div>
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Reçu inscription -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Recu_inscription <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="receipt_number" value="{{ old('receipt_number') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('receipt_number') border-red-500 @enderror"
                           placeholder="Ex: 0000">
                    @error('receipt_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <!-- Remplacez votre bouton actuel par celui-ci -->
                <button type="submit" name="action" value="add_sibling" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center text-sm transition focus:outline-none">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Ajouter Enfant de mêmes parents
                </button>
            </div>
        </div>

        <!-- Section 2: Détails du parent tuteur -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">2</span>
                    Détails du parent tuteur
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Père -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nom du père
                    </label>
                    <input type="text" name="father_name" value="{{ old('father_name', $parentDetails['father_name'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Téléphone du père
                    </label>
                    <input type="tel" name="father_phone" value="{{ old('father_phone', $parentDetails['father_phone'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Occupation du père
                    </label>
                    <input type="text" name="father_occupation" value="{{ old('father_occupation', $parentDetails['father_occupation'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Père Photo
                    </label>
                    <input type="file" name="father_photo" accept="image/*"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <!-- Mère -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nom de la mère
                    </label>
                    <input type="text" name="mother_name" value="{{ old('mother_name', $parentDetails['mother_name'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Téléphone de la mère
                    </label>
                    <input type="tel" name="mother_phone" value="{{ old('mother_phone', $parentDetails['mother_phone'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Occupation maternelle
                    </label>
                    <input type="text" name="mother_occupation" value="{{ old('mother_occupation', $parentDetails['mother_occupation'] ?? '') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Mère Photo
                    </label>
                    <input type="file" name="mother_photo" accept="image/*"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                                <!-- Gardien/Tuteur -->
                <div class="lg:col-span-4 border-t border-gray-200 pt-6 mt-2">
                    <h3 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Compte Espace Parent (Tuteur légal)
                    </h3>
                    <p class="text-sm text-gray-500 mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100">
                        💡 <strong>Important :</strong> Ces informations serviront à créer (ou lier) le compte de connexion du parent. Si cet email existe déjà, l'enfant sera simplement ajouté à son espace existant.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Le tuteur est <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="father" {{ old('guardian_type') == 'father' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary" onchange="toggleGuardianFields()">
                            <span class="ml-2 text-sm text-gray-700">Le Père</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="mother" {{ old('guardian_type') == 'mother' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary" onchange="toggleGuardianFields()">
                            <span class="ml-2 text-sm text-gray-700">La Mère</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="other" {{ old('guardian_type', 'other') == 'other' ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 focus:ring-primary" onchange="toggleGuardianFields()">
                            <span class="ml-2 text-sm text-gray-700">Autre</span>
                        </label>
                    </div>
                </div>

                <!-- NOUVEAU : Prénom et Nom séparés pour le compte utilisateur -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Prénom du tuteur <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="guardian_first_name" id="guardian_first_name" value="{{ old('guardian_first_name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nom du tuteur <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="guardian_last_name" id="guardian_last_name" value="{{ old('guardian_last_name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Courriel du tuteur <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="guardian_email" value="{{ old('guardian_email') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                    <p class="text-xs text-gray-500 mt-1">Sert d'identifiant de connexion pour l'espace parent.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Téléphone du gardien <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Relation avec l'élève
                    </label>
                    <input type="text" name="guardian_relation" value="{{ old('guardian_relation') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                           placeholder="Ex: Oncle, Tante, Grand-père...">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Profession du gardien
                    </label>
                    <input type="text" name="guardian_occupation" value="{{ old('guardian_occupation') }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Adresse du tuteur
                    </label>
                    <textarea name="guardian_address" rows="2"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition resize-none">{{ old('guardian_address') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Adresse Détails -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3 text-sm font-bold">3</span>
                    élève Adresse Détails
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex items-start">
                    <input type="checkbox" name="same_guardian_address" id="same_guardian_address" value="1"
                           class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                           onchange="toggleAddressFields()">
                    <label for="same_guardian_address" class="ml-3 text-sm text-gray-700">
                        Si l'adresse du tuteur est l'adresse actuelle
                    </label>
                </div>

                <div class="flex items-start">
                    <input type="checkbox" name="same_permanent_address" id="same_permanent_address" value="1"
                           class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                           onchange="togglePermanentAddressFields()">
                    <label for="same_permanent_address" class="ml-3 text-sm text-gray-700">
                        Si l'adresse permanente est l'adresse actuelle
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Adresse actuelle
                    </label>
                    <textarea name="current_address" id="current_address" rows="2"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition resize-none">{{ old('current_address') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Adresse permanente
                    </label>
                    <textarea name="permanent_address" id="permanent_address" rows="2"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition resize-none">{{ old('permanent_address') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 4: Détails divers -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3 text-sm font-bold">4</span>
                    Détails divers
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Détails de l'école précédente
                    </label>
                    <textarea name="previous_school" rows="3"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition resize-none"
                              placeholder="Nom de l'école, classe précédente, raisons du changement...">{{ old('previous_school') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Remarque
                    </label>
                    <textarea name="remarks" rows="3"
                              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition resize-none"
                              placeholder="Observations particulières, besoins spécifiques...">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 5: Télécharger des documents -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3 text-sm font-bold">5</span>
                    Télécharger des documents
                </h2>
                <p class="text-xs text-gray-500 mt-1 ml-11">Formats acceptés : PDF, DOC, DOCX, JPG, PNG — Taille maximale : 2 Mo par fichier</p>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                @for($i = 1; $i <= 4; $i++)
                <div class="js-file-dropzone-container">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ $i }}.
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label class="js-file-dropzone flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition"
                               data-max-size-mb="2" data-accept-ext="pdf,doc,docx,jpg,jpeg,png">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <p class="js-file-dropzone-label text-xs text-gray-500 text-center px-2">Drag and drop or click</p>
                            </div>
                            <input type="file" name="documents[{{ $i }}]" accept=".pdf,.doc,.docx,.jpg,.png" class="hidden">
                        </label>
                    </div>
                    <p class="js-file-dropzone-error text-xs text-red-500 mt-1 hidden"></p>
                    @error('documents.' . $i)
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endfor
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sticky bottom-4">
            <a href="{{ route('app.students.index') }}" class="inline-flex justify-center items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="inline-flex justify-center items-center px-8 py-3 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary-dark focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Sauvegarder
            </button>
        </div>

    </form>
</div>

<!-- JavaScript pour les fonctionnalités dynamiques -->
<script>
function toggleGuardianFields() {
    // Logique pour pré-remplir les champs du gardien si père ou mère est sélectionné
    const guardianType = document.querySelector('input[name="guardian_type"]:checked').value;
    const guardianName = document.querySelector('input[name="guardian_name"]');
    
    if (guardianType === 'father') {
        const fatherName = document.querySelector('input[name="father_name"]').value;
        if (fatherName) guardianName.value = fatherName;
    } else if (guardianType === 'mother') {
        const motherName = document.querySelector('input[name="mother_name"]').value;
        if (motherName) guardianName.value = motherName;
    }
}

function toggleAddressFields() {
    const checkbox = document.getElementById('same_guardian_address');
    const currentAddress = document.getElementById('current_address');
    const guardianAddress = document.querySelector('textarea[name="guardian_address"]');
    
    if (checkbox.checked && guardianAddress) {
        currentAddress.value = guardianAddress.value;
        currentAddress.readOnly = true;
        currentAddress.classList.add('bg-gray-100');
    } else {
        currentAddress.readOnly = false;
        currentAddress.classList.remove('bg-gray-100');
    }
}

function togglePermanentAddressFields() {
    const checkbox = document.getElementById('same_permanent_address');
    const permanentAddress = document.getElementById('permanent_address');
    const currentAddress = document.getElementById('current_address');
    
    if (checkbox.checked) {
        permanentAddress.value = currentAddress.value;
        permanentAddress.readOnly = true;
        permanentAddress.classList.add('bg-gray-100');
    } else {
        permanentAddress.readOnly = false;
        permanentAddress.classList.remove('bg-gray-100');
    }
}

// Générer automatiquement le matricule au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Si c'est un nouvel élève, générer un matricule unique
    const matriculeField = document.querySelector('input[name="matricule"]');
    if (matriculeField && !matriculeField.value) {
        // Format: MAT-ANNEE-NUMERO (ex: MAT-2024-0001)
        const year = new Date().getFullYear();
        const randomNum = Math.floor(Math.random() * 9000) + 1000;
        matriculeField.value = `MAT-${year}-${randomNum}`;
    }
});

// Générer automatiquement le matricule et le numéro d'admission au chargement
document.addEventListener('DOMContentLoaded', function() {
    const year = new Date().getFullYear();

    // 1. Générer le Matricule
    const matriculeField = document.querySelector('input[name="matricule"]');
    if (matriculeField && !matriculeField.value) {
        const randomNum = Math.floor(Math.random() * 9000) + 1000;
        matriculeField.value = `MAT-${year}-${randomNum}`;
    }

    // 2. Générer le Numéro d'admission
    const admissionField = document.querySelector('input[name="admission_number"]');
    if (admissionField && !admissionField.value) {
        const randomNum = Math.floor(Math.random() * 9000) + 1000;
        admissionField.value = `ADM-${year}-${randomNum}`;
    }
});

// Glisser-déposer pour les zones de téléchargement de documents
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-file-dropzone').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        const label = zone.querySelector('.js-file-dropzone-label');
        const errorEl = zone.closest('.js-file-dropzone-container')?.querySelector('.js-file-dropzone-error');
        const defaultText = label ? label.textContent : '';
        const maxSizeBytes = (parseFloat(zone.dataset.maxSizeMb) || 2) * 1024 * 1024;
        const acceptedExt = (zone.dataset.acceptExt || '').split(',').map(function (ext) { return ext.trim().toLowerCase(); });

        function showError(message) {
            if (!errorEl) return;
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }

        function clearError() {
            if (!errorEl) return;
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }

        function validateFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (acceptedExt.length && !acceptedExt.includes(ext)) {
                return `Format non accepté (.${ext}). Formats acceptés : ${acceptedExt.join(', ')}.`;
            }
            if (file.size > maxSizeBytes) {
                const maxMb = (maxSizeBytes / (1024 * 1024)).toFixed(1);
                const fileMb = (file.size / (1024 * 1024)).toFixed(1);
                return `Fichier trop volumineux (${fileMb} Mo). Taille maximale : ${maxMb} Mo.`;
            }
            return null;
        }

        function showSelectedFile() {
            if (!label) return;
            if (input.files.length > 1) {
                label.textContent = `${input.files.length} fichiers sélectionnés`;
            } else if (input.files.length === 1) {
                label.textContent = input.files[0].name;
            } else {
                label.textContent = defaultText;
            }
        }

        function acceptFiles(files) {
            if (!files || !files.length) return;
            const error = validateFile(files[0]);
            if (error) {
                showError(error);
                input.value = '';
                showSelectedFile();
                return;
            }
            clearError();
            input.files = files;
            showSelectedFile();
        }

        ['dragenter', 'dragover'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('border-primary', 'bg-blue-50');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('border-primary', 'bg-blue-50');
            });
        });

        zone.addEventListener('drop', function (e) {
            if (!input || !e.dataTransfer || !e.dataTransfer.files.length) return;
            acceptFiles(e.dataTransfer.files);
        });

        if (input) {
            input.addEventListener('change', function () {
                if (!input.files.length) {
                    clearError();
                    showSelectedFile();
                    return;
                }
                const error = validateFile(input.files[0]);
                if (error) {
                    showError(error);
                    input.value = '';
                    showSelectedFile();
                    return;
                }
                clearError();
                showSelectedFile();
            });
        }
    });
});
</script>
@endsection