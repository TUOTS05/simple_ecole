{{--
    Section 5 "Télécharger des documents", intégralement identique entre students/create
    et enrollments/create (aucune variable requise en dehors de old()/$errors).
--}}
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
