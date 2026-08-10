@extends('layouts.app')

@section('title', 'Paiements Cantine')
@section('page_title', 'Paiements de la Cantine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="canteenPaymentForm()">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Formulaire de Paiement Dynamique -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💳 Enregistrer un Paiement</h3>
        
        <form action="{{ route('canteen.payments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classe *</label>
                    <select x-model="selectedClassId" @change="fetchSubscriptions" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="">-- Choisir une classe --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Élève (avec reste à payer) *</label>
                    <select name="canteen_subscription_id" x-model="selectedSubscriptionId" @change="updatePaymentDetails" :disabled="!selectedClassId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white disabled:bg-gray-100">
                        <option value="">-- Choisir un élève --</option>
                        <template x-for="sub in subscriptions" :key="sub.id">
                            <option :value="sub.id" x-text="sub.student_name + ' (' + sub.matricule + ') - Reste: ' + formatMoney(sub.remaining) + ' FCFA'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement *</label>
                    <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="cash">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="transfer">Virement</option>
                        <option value="check">Chèque</option>
                    </select>
                </div>
            </div>

            <div x-show="selectedSubscription" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-blue-900 mb-2"> Mois concernés par ce paiement</h4>
                        <p class="text-sm text-blue-800 mb-2">Le paiement sera automatiquement réparti sur les mois les plus anciens en premier :</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="month in selectedSubscription?.unpaid_months" :key="month">
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full" x-text="month"></span>
                            </template>
                            <span x-show="!selectedSubscription?.unpaid_months || selectedSubscription.unpaid_months.length === 0" class="text-sm text-gray-500">
                                Aucun mois impayé.
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reste à payer total</label>
                                <input type="text" readonly :value="selectedSubscription ? formatMoney(selectedSubscription.remaining) + ' FCFA' : '0 FCFA'" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 font-bold text-gray-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant à encaisser (FCFA) *</label>
                                <input type="number" name="amount" x-model="paymentAmount" required min="1" class="w-full px-4 py-2 border border-primary rounded-lg focus:ring-2 focus:ring-primary font-bold text-primary">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date du paiement *</label>
                                <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Référence (Optionnel)</label>
                                <input type="text" name="reference" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary" placeholder="N° transaction">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="!selectedSubscriptionId" class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-8 py-3 rounded-lg font-semibold transition">
                    💰 Enregistrer le Paiement
                </button>
            </div>
        </form>
    </div>

    <!-- ✅ HISTORIQUE DES PAIEMENTS : AFFICHAGE CONDITIONNEL -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden" x-data="{ showResults: {{ $classId || $month ? 'true' : 'false' }} }">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h3 class="text-lg font-bold text-gray-800">📜 Historique des Paiements</h3>
                
                <!-- Formulaire de filtres -->
                <form method="GET" action="{{ route('canteen.payments.index') }}" class="flex flex-wrap items-end gap-3 w-full lg:w-auto">
                    <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">
                    
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Classe</label>
                        <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary bg-white">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mois</label>
                        <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary bg-white">
                            <option value="">Tous les mois</option>
                            @foreach($availableMonths as $value => $label)
                                <option value="{{ $value }}" {{ $month == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-1 h-[38px]">
                        🔍 Rechercher
                    </button>
                    
                    @if($classId || $month)
                        <a href="{{ route('canteen.payments.index', ['school_year_id' => $schoolYearId]) }}" class="text-sm text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-2 rounded-lg transition flex items-center gap-1 h-[38px]">
                            ✕ Réinitialiser
                        </a>
                    @endif
                </form>
            </div>
        </div>
        
        <!-- ✅ Affichage conditionnel : vide par défaut, résultats après recherche -->
        <div x-show="showResults" x-transition>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Montant</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Mode</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Référence</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Reçu par</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4 text-sm text-gray-800">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800">
                                {{ $payment->subscription->student->last_name }} {{ $payment->subscription->student->first_name }}
                                <div class="text-xs text-gray-500 font-mono">{{ $payment->subscription->student->matricule }}</div>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->subscription->canteenRate->schoolClass->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-right text-green-700 font-bold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="py-3 px-4 text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                            <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $payment->reference ?? '-' }}</td>
                            <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->receivedByUser->name ?? 'Système' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500">
                                Aucun paiement trouvé pour ces critères de recherche.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $payments->links() }}
            </div>
            @endif
        </div>

        <!-- ✅ Message d'accueil quand aucun filtre n'est appliqué -->
        <div x-show="!showResults" class="py-16 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <p class="text-gray-500 text-lg font-medium">Sélectionnez une classe et/ou un mois pour afficher l'historique</p>
            <p class="text-gray-400 text-sm mt-2">Utilisez les filtres ci-dessus pour rechercher des paiements</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function canteenPaymentForm() {
        return {
            selectedClassId: '',
            selectedSubscriptionId: '',
            subscriptions: [],
            selectedSubscription: null,
            paymentAmount: '',

            async fetchSubscriptions() {
                this.subscriptions = [];
                this.selectedSubscriptionId = '';
                this.selectedSubscription = null;
                this.paymentAmount = '';
                
                if (!this.selectedClassId) return;

                try {
                    const schoolYearId = document.querySelector('input[name="school_year_id"]').value;
                    const response = await fetch(`/canteen/subscriptions-by-class?class_id=${this.selectedClassId}&school_year_id=${schoolYearId}`);
                    this.subscriptions = await response.json();
                } catch (error) {
                    console.error('Erreur:', error);
                }
            },

            updatePaymentDetails() {
                this.selectedSubscription = this.subscriptions.find(s => s.id == this.selectedSubscriptionId) || null;
                if (this.selectedSubscription) {
                    this.paymentAmount = this.selectedSubscription.remaining;
                } else {
                    this.paymentAmount = '';
                }
            },

            formatMoney(amount) {
                return new Intl.NumberFormat('fr-FR').format(amount);
            }
        }
    }
</script>
@endpush
@endsection