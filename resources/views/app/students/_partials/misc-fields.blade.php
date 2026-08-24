{{--
    Section 4 "Détails divers", intégralement identique entre students/create et
    enrollments/create (aucune variable requise en dehors de old()).
--}}
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
