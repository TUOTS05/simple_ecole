@extends('layouts.app')

@section('title', 'Configurer les Frais')
@section('page_title', 'Configuration des Frais')

@section('content')
<div class="max-w-3xl mx-auto">
    
    <!-- En-tête -->
    <div class="mb-6 flex items-center">
        <a href="{{ route('app.class-fees.index') }}" class="mr-4 p-2 rounded-full hover:bg-gray-200 transition">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configurer les frais pour : {{ $schoolClass->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">Définissez les montants et modalités de paiement.</p>
        </div>
    </div>

    <form action="{{ route('app.class-fees.update', $schoolClass->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Montants</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Scolarité Totale -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Montant total de la scolarité (FCFA) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="total_tuition" 
                           value="{{ old('total_tuition', $schoolClass->total_tuition ?? '') }}" 
                           required min="0" step="100"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('total_tuition') border-red-500 @enderror">
                    @error('total_tuition')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Frais d'Inscription -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Montant à payer à l'inscription (FCFA) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="registration_fee" 
                           value="{{ old('registration_fee', $schoolClass->registration_fee ?? '') }}" 
                           required min="0" step="100"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('registration_fee') border-red-500 @enderror">
                    @error('registration_fee')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Résumé automatique -->
            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-800">
                    <strong>Reste à payer après inscription :</strong> 
                    <span id="remaining-amount">{{ number_format(($schoolClass->total_tuition ?? 0) - ($schoolClass->registration_fee ?? 0), 0, ',', ' ') }}</span> FCFA
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Modalités de paiement</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Modalité de paiement -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Modalité de paiement <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_modality" id="payment_modality" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('payment_modality') border-red-500 @enderror">
                        <option value="unique" {{ old('payment_modality', $schoolClass->payment_modality ?? 'unique') == 'unique' ? 'selected' : '' }}>
                            Paiement unique (tout le reste d'un coup)
                        </option>
                        <option value="mensuel" {{ old('payment_modality', $schoolClass->payment_modality ?? '') == 'mensuel' ? 'selected' : '' }}>
                            Paiement mensuel
                        </option>
                        <option value="trimestriel" {{ old('payment_modality', $schoolClass->payment_modality ?? '') == 'trimestriel' ? 'selected' : '' }}>
                            Paiement trimestriel
                        </option>
                        <option value="semestriel" {{ old('payment_modality', $schoolClass->payment_modality ?? '') == 'semestriel' ? 'selected' : '' }}>
                            Paiement semestriel
                        </option>
                    </select>
                    @error('payment_modality')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre d'échéances -->
                <div id="installments-field">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nombre d'échéances <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="number_of_installments" id="number_of_installments"
                           value="{{ old('number_of_installments', $schoolClass->number_of_installments ?? 1) }}" 
                           min="1" max="12"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('number_of_installments') border-red-500 @enderror">
                    @error('number_of_installments')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Calcul automatique -->
            <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                <p class="text-sm text-green-800">
                    <strong>Montant par échéance :</strong> 
                    <span id="installment-amount">{{ number_format($schoolClass->installment_amount ?? 0, 0, ',', ' ') }}</span> FCFA
                </p>
                <p class="text-xs text-green-600 mt-1" id="installment-info">
                    (Calculé automatiquement)
                </p>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <a href="{{ route('app.class-fees.index') }}" 
               class="inline-flex justify-center items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
                Annuler
            </a>
            <button type="submit" 
                    class="inline-flex justify-center items-center px-8 py-3 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary-dark focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg transition transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Enregistrer la configuration
            </button>
        </div>
    </form>
</div>

<script>
// Calcul automatique des montants
function calculateAmounts() {
    const totalTuition = parseFloat(document.querySelector('input[name="total_tuition"]').value) || 0;
    const registrationFee = parseFloat(document.querySelector('input[name="registration_fee"]').value) || 0;
    const modality = document.getElementById('payment_modality').value;
    const installmentsField = document.getElementById('installments-field');
    const numberOfInstallments = parseInt(document.getElementById('number_of_installments').value) || 1;

    const remainingAmount = totalTuition - registrationFee;
    document.getElementById('remaining-amount').textContent = remainingAmount.toLocaleString('fr-FR');

    let installmentAmount = 0;
    
    if (modality === 'unique') {
        installmentAmount = remainingAmount;
        installmentsField.style.display = 'none';
        document.getElementById('installment-info').textContent = '(Paiement unique du reste)';
    } else {
        installmentsField.style.display = 'block';
        installmentAmount = remainingAmount / numberOfInstallments;
        document.getElementById('installment-info').textContent = `(${remainingAmount.toLocaleString('fr-FR')} FCFA ÷ ${numberOfInstallments} échéances)`;
    }

    document.getElementById('installment-amount').textContent = installmentAmount.toLocaleString('fr-FR');
}

// Écouteurs d'événements
document.querySelector('input[name="total_tuition"]').addEventListener('input', calculateAmounts);
document.querySelector('input[name="registration_fee"]').addEventListener('input', calculateAmounts);
document.getElementById('payment_modality').addEventListener('change', calculateAmounts);
document.getElementById('number_of_installments').addEventListener('input', calculateAmounts);

// Calcul initial
calculateAmounts();
</script>
@endsection