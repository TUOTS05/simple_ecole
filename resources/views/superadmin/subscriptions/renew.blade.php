@extends('layouts.app')

@section('title', 'Renouveler le contrat')
@section('page_title', 'Renouveler l\'abonnement de ' . $contract->school->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">
        
        <!-- Info sur l'ancien contrat -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
            <p class="text-blue-800 font-semibold">🔄 Renouvellement du contrat {{ $contract->contract_number }}</p>
            <p class="text-sm text-blue-700 mt-1">
                Ancienne période : {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} 
                au {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
            </p>
            <p class="text-sm text-blue-700">
                Un <strong>nouveau contrat</strong> sera généré avec un nouveau numéro. L'ancien restera archivé.
            </p>
        </div>

        <form action="{{ route('superadmin.subscriptions.store-renewal', $contract) }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouvelle date de début *</label>
                        <input type="date" name="start_date" value="{{ $newStartDate }}" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouvelle date de fin *</label>
                        <input type="date" name="end_date" value="{{ $newEndDate }}" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <!-- Montant -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau montant facturé (FCFA) *</label>
                    <input type="number" name="amount" value="{{ $contract->amount }}" required min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Modifiable en cas de remise ou d'augmentation tarifaire.</p>
                </div>
            </div>

            <!-- Boutons -->
            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-4">
                <a href="{{ route('superadmin.subscriptions.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                    Annuler
                </a>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition shadow-md flex items-center">
                    <span class="mr-2">🔄</span> Générer le nouveau contrat
                </button>
            </div>
        </form>
    </div>
</div>
@endsection