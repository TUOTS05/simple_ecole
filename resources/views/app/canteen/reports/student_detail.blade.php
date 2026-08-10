@extends('layouts.app')

@section('title', 'Détail Cantine - ' . $student->first_name . ' ' . $student->last_name)
@section('page_title', 'Détail Cantine : ' . strtoupper($student->last_name) . ' ' . $student->first_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- En-tête et Bouton Retour -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                {{ strtoupper($student->last_name) }} {{ $student->first_name }}
            </h2>
            <p class="text-sm text-gray-500">
                Matricule : <span class="font-mono font-medium">{{ $student->matricule }}</span> | 
                Classe : <span class="font-medium">{{ $subscription->canteenRate->schoolClass->name ?? 'N/A' }}</span>
            </p>
        </div>
        <a href="{{ route('canteen.reports.class_detail', ['classId' => $subscription->canteenRate->schoolClass->id ?? 1, 'school_year_id' => $schoolYearId]) }}" 
           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition flex items-center gap-2">
            ← Retour à la classe
        </a>
    </div>

    <!-- Résumé Financier de l'élève -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Dû (Annuel)</div>
            <div class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($subscription->total_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Payé</div>
            <div class="text-2xl font-bold text-green-600 mt-2">{{ number_format($subscription->paid_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Reste à Payer</div>
            <div class="text-2xl font-bold text-red-600 mt-2">{{ number_format($subscription->remaining_amount, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Taux de Recouvrement</div>
            <div class="text-2xl font-bold text-primary mt-2">{{ $subscription->payment_rate }}%</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tableau des Échéances -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">📅 Échéances Mensuelles</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Mois</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-600">Montant</th>
                            <th class="text-right py-3 px-4 font-semibold text-green-600">Payé</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-600">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($installments as $inst)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">
                                {{ \Carbon\Carbon::parse($inst->month . '-01')->translatedFormat('F Y') }}
                                <div class="text-xs text-gray-500">Échéance: {{ \Carbon\Carbon::parse($inst->due_date)->format('d/m/Y') }}</div>
                            </td>
                            <td class="py-3 px-4 text-right">{{ number_format($inst->amount, 0, ',', ' ') }}</td>
                            <td class="py-3 px-4 text-right text-green-700">{{ number_format($inst->paid_amount, 0, ',', ' ') }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($inst->status === 'paid')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Payé</span>
                                @elseif($inst->status === 'partial')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">Partiel</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Impayé</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-500">Aucune échéance générée.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tableau des Paiements Effectués -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">💳 Historique des Paiements</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                            <th class="text-right py-3 px-4 font-semibold text-green-600">Montant</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Mode</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600">Référence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4">
                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                                <div class="text-xs text-gray-500">Reçu par: {{ $payment->receivedByUser->name ?? 'Système' }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-green-700">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                            <td class="py-3 px-4 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                            <td class="py-3 px-4 font-mono text-gray-600">{{ $payment->reference ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-500">Aucun paiement enregistré pour cet élève.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection