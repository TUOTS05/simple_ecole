@extends('layouts.app')

@section('title', 'Modifier l\'Élève')
@section('page_title', 'Modifier l\'Élève')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <!-- En-tête -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Modifier : {{ strtoupper($student->last_name ?? '') }} {{ $student->first_name ?? '' }}</h1>
            <p class="text-sm text-gray-500 mt-1">Matricule : <span class="font-mono font-semibold">{{ $student->matricule ?? 'N/A' }}</span></p>
        </div>
        <a href="{{ route('app.students.show', $student->id) }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour au profil
        </a>
    </div>

    <form action="{{ route('app.students.update', $student->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Section 1: Informations de l'élève -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm font-bold">1</span>
                    Informations de l'élève
                </h2>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Numéro Admission</label>
                    <input type="text" value="{{ $student->admission_number ?? '' }}" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Matricule</label>
                    <input type="text" value="{{ $student->matricule ?? '' }}" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Classe <span class="text-red-500">*</span></label>
                    <select name="class_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('class_id') border-red-500 @enderror">
                        <option value="">Sélectionner</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id', $student->classes->first()?->id) == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Section</label>
                    <select name="section" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Sélectionner</option>
                        <option value="A" {{ old('section', $student->section ?? '') == 'A' ? 'selected' : '' }}>Section A</option>
                        <option value="B" {{ old('section', $student->section ?? '') == 'B' ? 'selected' : '' }}>Section B</option>
                        <option value="C" {{ old('section', $student->section ?? '') == 'C' ? 'selected' : '' }}>Section C</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prénom <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $student->first_name ?? '') }}" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('first_name') border-red-500 @enderror">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom de famille <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $student->last_name ?? '') }}" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('last_name') border-red-500 @enderror">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Genre <span class="text-red-500">*</span></label>
                    <select name="gender" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('gender') border-red-500 @enderror">
                        <option value="M" {{ old('gender', $student->gender ?? '') == 'M' ? 'selected' : '' }}>Masculin</option>
                        <option value="F" {{ old('gender', $student->gender ?? '') == 'F' ? 'selected' : '' }}>Féminin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date de naissance <span class="text-red-500">*</span></label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date ? $student->birth_date->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('birth_date') border-red-500 @enderror">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Statut <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', $student->status ?? 'active') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="inactive" {{ old('status', $student->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                        <option value="suspended" {{ old('status', $student->status ?? '') == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Famille nombreuse</label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="large_family" value="1" {{ old('large_family', $student->large_family ?? 0) == 1 ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">OUI</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="large_family" value="0" {{ old('large_family', $student->large_family ?? 0) == 0 ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">NON</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Enfant du personnel</label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="staff_child" value="1" {{ old('staff_child', $student->staff_child ?? 0) == 1 ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">OUI</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="staff_child" value="0" {{ old('staff_child', $student->staff_child ?? 0) == 0 ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">NON</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Religion</label>
                    <select name="religion" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Sélectionner</option>
                        <option value="catholique" {{ old('religion', $student->religion ?? '') == 'catholique' ? 'selected' : '' }}>Catholique</option>
                        <option value="protestant" {{ old('religion', $student->religion ?? '') == 'protestant' ? 'selected' : '' }}>Protestant</option>
                        <option value="musulman" {{ old('religion', $student->religion ?? '') == 'musulman' ? 'selected' : '' }}>Musulman</option>
                        <option value="autre" {{ old('religion', $student->religion ?? '') == 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Date d'admission</label>
                    <input type="date" name="admission_date" value="{{ old('admission_date', $student->admission_date ? $student->admission_date->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Photo de l'élève</label>
                    @if($student->photo)
                        <div class="mb-2 flex items-center gap-2">
                            <img src="{{ asset('storage/' . $student->photo) }}" class="w-10 h-10 rounded-full object-cover border">
                            <a href="{{ asset('storage/' . $student->photo) }}" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Voir l'actuelle
                            </a>
                        </div>
                    @endif
                    <input type="file" name="student_photo" accept="image/*" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Laissez vide pour conserver l'actuelle.</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">N° Reçu inscription</label>
                    <input type="text" name="receipt_number" value="{{ old('receipt_number', $student->receipt_number ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Section 2: Détails du parent / tuteur -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm font-bold">2</span>
                    Détails du parent / tuteur
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Père -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom du père</label>
                    <input type="text" name="father_name" value="{{ old('father_name', $student->father_name ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone du père</label>
                    <input type="tel" name="father_phone" value="{{ old('father_phone', $student->father_phone ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Occupation du père</label>
                    <input type="text" name="father_occupation" value="{{ old('father_occupation', $student->father_occupation ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <!-- Mère -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom de la mère</label>
                    <input type="text" name="mother_name" value="{{ old('mother_name', $student->mother_name ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone de la mère</label>
                    <input type="tel" name="mother_phone" value="{{ old('mother_phone', $student->mother_phone ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Occupation de la mère</label>
                    <input type="text" name="mother_occupation" value="{{ old('mother_occupation', $student->mother_occupation ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <!-- Gardien/Tuteur -->
                <div class="lg:col-span-4 border-t border-gray-200 pt-6 mt-2">
                    <h3 class="text-md font-semibold text-gray-700 mb-4">Informations du tuteur légal</h3>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Le gardien est</label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="father" {{ old('guardian_type', $student->guardian_type ?? 'other') == 'father' ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Père</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="mother" {{ old('guardian_type', $student->guardian_type ?? 'other') == 'mother' ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Mère</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="guardian_type" value="other" {{ old('guardian_type', $student->guardian_type ?? 'other') == 'other' ? 'checked' : '' }} class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Autre</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom du gardien</label>
                    <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Relation avec l'élève</label>
                    <input type="text" name="guardian_relation" value="{{ old('guardian_relation', $student->guardian_relation ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Courriel du tuteur</label>
                    <input type="email" name="guardian_email" value="{{ old('guardian_email', $student->guardian_email ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Téléphone du gardien</label>
                    <input type="tel" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Occupation du gardien</label>
                    <input type="text" name="guardian_occupation" value="{{ old('guardian_occupation', $student->guardian_occupation ?? '') }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse du tuteur</label>
                    <textarea name="guardian_address" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none">{{ old('guardian_address', $student->guardian_address ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Adresses -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-3 text-sm font-bold">3</span>
                    Adresses
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse actuelle</label>
                    <textarea name="current_address" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none">{{ old('current_address', $student->current_address ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse permanente</label>
                    <textarea name="permanent_address" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none">{{ old('permanent_address', $student->permanent_address ?? '') }}</textarea>
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
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">École précédente</label>
                    <textarea name="previous_school" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none">{{ old('previous_school', $student->previous_school ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Remarques</label>
                    <textarea name="remarks" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary resize-none">{{ old('remarks', $student->remarks ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 5: Documents (Explication sur les fichiers) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-3 text-sm font-bold">5</span>
                    Documents
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800 flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>
                            <strong>Note importante :</strong> Pour des raisons de sécurité, les navigateurs interdisent de pré-remplir les champs de fichiers. 
                            Si vous souhaitez modifier un document, sélectionnez simplement le nouveau fichier ci-dessous. Il remplacera l'ancien.
                        </span>
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @for($i = 1; $i <= 4; $i++)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Document {{ $i }}</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="text-xs text-gray-500">Cliquer pour remplacer le document {{ $i }}</p>
                                </div>
                                <input type="file" name="documents[{{ $i }}]" accept=".pdf,.doc,.docx,.jpg,.png" class="hidden">
                            </label>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sticky bottom-4">
            <a href="{{ route('app.students.show', $student->id) }}" class="inline-flex justify-center items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="inline-flex justify-center items-center px-8 py-3 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary-dark focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Mettre à jour
            </button>
        </div>

    </form>
</div>
@endsection