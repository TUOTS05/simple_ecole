{{--
    Champs "Mère" (nom, téléphone, occupation), communs à students/create et
    enrollments/create. Le champ mother_photo (présent uniquement dans students/create)
    est volontairement laissé en dehors de ce partiel et géré par le fichier appelant.

    Variable attendue :
    - $parentDetails : array, infos parent en session (peut être vide)
--}}
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
