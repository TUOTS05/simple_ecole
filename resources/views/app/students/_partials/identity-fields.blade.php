{{--
    Champs communs de la Section 1 (Informations d'admission) : classe, section, identité,
    genre, date de naissance, statut, famille nombreuse, enfant du personnel, religion,
    date d'admission et photo de l'élève.

    Champs volontairement EXCLUS (diffèrent entre students/create et enrollments/create,
    laissés dans les fichiers appelants) : admission_number, matricule, receipt_number,
    bouton "Ajouter Enfant de mêmes parents".

    Variables attendues :
    - $classes : liste des classes de l'école (Illuminate\Support\Collection)
    - $requireFamilyChoice : bool, optionnel (défaut false). Reproduit à l'identique une
      différence existante entre les deux formulaires : sur enrollments/create, le radio
      "OUI" de "large_family" et "staff_child" porte l'attribut HTML required, pas sur
      students/create.
--}}
@php
    $requireFamilyChoice = $requireFamilyChoice ?? false;
@endphp

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
        <option value="inactive" selected>{{ $statusOptionLabel ?? 'Inactif (En attente de paiement)' }}</option>
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
            <input type="radio" name="large_family" value="1" {{ old('large_family') == '1' ? 'checked' : '' }} {{ $requireFamilyChoice ? 'required' : '' }}
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
            <input type="radio" name="staff_child" value="1" {{ old('staff_child') == '1' ? 'checked' : '' }} {{ $requireFamilyChoice ? 'required' : '' }}
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
