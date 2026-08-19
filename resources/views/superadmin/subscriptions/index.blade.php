@extends('layouts.app')

@section('title', 'Abonnements')
@section('page_title', 'Gérer les abonnements')

@section('content')
<div class="max-w-6xl mx-auto">
    
    <!-- Message de succès -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1 : FORMULAIRE DE CRÉATION DE CONTRAT -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800">Contrats</h2>
            <button onclick="document.getElementById('contractForm').reset()" class="text-sm text-gray-600 hover:text-gray-800 flex items-center">
                <span class="mr-1">+</span> Annuler
            </button>
        </div>

        <form action="{{ route('superadmin.subscriptions.store') }}" method="POST" id="contractForm">
            @csrf

            <!-- ÉCOLE ET PLAN -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">École et Plan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- École -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">École *</label>
                        <select name="school_id" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('school_id') border-red-500 @enderror">
                            <option value="">Choisir une école</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }} 
                                    @if($school->status === 'suspended')
                                        (En attente)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('school_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Plan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plan *</label>
                        <select name="plan_id" id="planSelect" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('plan_id') border-red-500 @enderror">
                            <option value="">Choisir un plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" 
                                    data-price="{{ $plan->yearly_price }}"  {{-- ✅ Changé de price à yearly_price --}}
                                    data-name="{{ $plan->name }}"
                                    {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - {{ number_format($plan->yearly_price, 0, ',', ' ') }} FCFA/an
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- PÉRIODE ET MONTANT -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Période et Montant</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Début du contrat -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Début du contrat *</label>
                        <input type="date" name="start_date" id="startDate" value="{{ old('start_date', date('Y-m-d')) }}" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('start_date') border-red-500 @enderror">
                        @error('start_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Fin du contrat -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fin du contrat *</label>
                        <input type="date" name="end_date" id="endDate" value="{{ old('end_date', date('Y-m-d', strtotime('+1 year'))) }}" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('end_date') border-red-500 @enderror">
                        @error('end_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Montant facturé -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant facturé (FCFA) *</label>
                        <input type="number" name="amount" id="amountField" value="{{ old('amount') }}" required min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('amount') border-red-500 @enderror">
                        @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-500 mt-1">Peut différer du prix catalogue en cas de remise négociée.</p>
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                <button type="button" onclick="document.getElementById('contractForm').reset()" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                    Annuler
                </button>
                <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition shadow-md">
                    Créer le contrat
                </button>
            </div>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2 : LISTE DES CONTRATS -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">École</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($contracts as $contract)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $contract->school->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <!-- APRÈS (utilise la colonne texte) -->
                                <div class="text-sm text-gray-900">{{ $contract->plan_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} → 
                                    {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($contract->amount, 0, ',', ' ') }} FCFA</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Seuls active/expired/renewed sont réellement produits par le
                                    // code (SubscriptionController) ; le repli générique ci-dessous
                                    // couvre tout statut inattendu sans avoir à lister des valeurs mortes.
                                    $statusClass = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'expired' => 'bg-red-100 text-red-800',
                                        'renewed' => 'bg-blue-100 text-blue-800',
                                    ][$contract->status] ?? 'bg-gray-100 text-gray-800';

                                    $statusLabel = [
                                        'active' => 'Actif',
                                        'expired' => 'Expiré',
                                        'renewed' => 'Renouvelé',
                                    ][$contract->status] ?? $contract->status;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                           <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
    @if($contract->pdf_path)
        <a href="{{ asset('storage/' . $contract->pdf_path) }}" target="_blank" 
            class="text-blue-600 hover:text-blue-900 mr-3" title="Télécharger le PDF">
            📥
        </a>
    @endif
    
    {{-- Bouton de renouvellement (visible si le contrat est expiré ou expire dans moins de 30 jours) --}}
    @php
        $isExpiringSoon = \Carbon\Carbon::parse($contract->end_date)->diffInDays(now()) <= 30 
                          && \Carbon\Carbon::parse($contract->end_date)->isFuture();
        $isExpired = \Carbon\Carbon::parse($contract->end_date)->isPast();
    @endphp

    @if($isExpiringSoon || $isExpired || $contract->status === 'active')
        <a href="{{ route('superadmin.subscriptions.renew', $contract) }}" 
           class="text-green-600 hover:text-green-900 font-semibold" title="Renouveler l'abonnement">
            🔄 Renouveler
        </a>
    @endif
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Aucun contrat enregistré
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Script pour auto-remplir le montant -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const planSelect = document.getElementById('planSelect');
        const amountField = document.getElementById('amountField');
        
        planSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price && !amountField.value) {
                amountField.value = price;
            }
        });
    });
</script>
@endsection