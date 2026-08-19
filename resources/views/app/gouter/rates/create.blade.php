@extends('layouts.app')

@section('title', 'Nouveau Tarif Goûter')
@section('page_title', 'Créer un Tarif de Goûter (Maternelle)')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="gouterRateForm()">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form action="{{ route('gouter.rates.store') }}" method="POST">
            @csrf

            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe (Maternelle) *</label>
                    <select name="school_class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                        <option value="">-- Choisir --</option>
                        @forelse($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @empty
                            <option value="" disabled>Aucune classe de maternelle trouvée</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant Annuel (FCFA) *</label>
                    <input type="number" name="total_amount" x-model.number="totalAmount" required min="0" step="100"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Modalité de paiement *</label>
                <select name="payment_modality" x-model="modality" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="unique">Unique (1 versement)</option>
                    <option value="semestriel">Semestriel (2 versements)</option>
                    <option value="trimestriel">Trimestriel (3 versements)</option>
                    <option value="mensuel">Mensuel (10 versements)</option>
                </select>
                <p class="text-xs text-gray-500 mt-2" x-show="totalAmount > 0">
                    💡 <span x-text="installmentsCount"></span> échéance(s) de <span x-text="formatMoney(installmentAmount)"></span> FCFA chacune.
                </p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                          placeholder="Ex: Tarif goûter PS - année 2026-2027"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('gouter.rates.index', ['school_year_id' => $schoolYearId]) }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    💾 Créer le Tarif
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function gouterRateForm() {
        return {
            totalAmount: 0,
            modality: 'unique',
            get installmentsCount() {
                return { unique: 1, semestriel: 2, trimestriel: 3, mensuel: 10 }[this.modality] ?? 1;
            },
            get installmentAmount() {
                return this.installmentsCount > 0 ? Math.round(this.totalAmount / this.installmentsCount) : 0;
            },
            formatMoney(amount) {
                return new Intl.NumberFormat('fr-FR').format(amount);
            }
        }
    }
</script>
@endpush
@endsection
