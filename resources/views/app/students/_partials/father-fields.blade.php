{{--
    Champs "Père" (nom, téléphone, occupation), communs à students/create et
    enrollments/create. Le champ father_photo (présent uniquement dans students/create)
    est volontairement laissé en dehors de ce partiel et géré par le fichier appelant.

    Variable attendue :
    - $parentDetails : array, infos parent en session (peut être vide)
--}}
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
