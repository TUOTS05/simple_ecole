{{--
    Bloc "Compte Espace Parent (Tuteur légal)", commun à students/create et
    enrollments/create.

    Variables optionnelles (préservent des différences mineures et existantes entre
    les deux formulaires d'origine) :
    - $guardianOccupationLabel : string, libellé du champ guardian_occupation.
      Défaut 'Profession du gardien' (students/create). enrollments/create utilisait
      'Occupation du gardien'.
    - $showGuardianPhoneError : bool, défaut false. enrollments/create affichait la
      classe d'erreur + le message @error sur guardian_phone, pas students/create.
--}}
@php
    $guardianOccupationLabel = $guardianOccupationLabel ?? 'Profession du gardien';
    $showGuardianPhoneError = $showGuardianPhoneError ?? false;
@endphp

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
           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @if($showGuardianPhoneError) @error('guardian_phone') border-red-500 @enderror @endif">
    @if($showGuardianPhoneError)
        @error('guardian_phone')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    @endif
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
        {{ $guardianOccupationLabel }}
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
