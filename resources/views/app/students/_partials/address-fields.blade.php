{{--
    Section 3 "élève Adresse Détails", intégralement identique entre students/create et
    enrollments/create (aucune variable requise en dehors de old()).
--}}
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
