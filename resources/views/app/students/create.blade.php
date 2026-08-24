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

        @include('app.students._partials.validation-errors')

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

                @include('app.students._partials.identity-fields', ['classes' => $classes])

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
                @include('app.students._partials.father-fields', ['parentDetails' => $parentDetails ?? []])

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Père Photo
                    </label>
                    <input type="file" name="father_photo" accept="image/*"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                @include('app.students._partials.mother-fields', ['parentDetails' => $parentDetails ?? []])

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Mère Photo
                    </label>
                    <input type="file" name="mother_photo" accept="image/*"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition">
                </div>

                @include('app.students._partials.guardian-fields')
            </div>
        </div>

        @include('app.students._partials.address-fields')

        @include('app.students._partials.misc-fields')

        @include('app.students._partials.documents-fields')

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