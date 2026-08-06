@extends('layouts.app')

@section('title', 'État Financier - ' . $student->first_name . ' ' . $student->last_name)
@section('page_title', 'État Financier de l\'Élève')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Retour -->
    <div>
        <a href="{{ route('app.financial.unpaid_by_class', ['school_year_id' => $schoolYearId]) }}" 
           class="text-primary hover:text-primary-dark font-semibold flex items-center gap-2">
            ← Retour aux rapports financiers
        </a>
    </div>

    <!-- En-tête avec boutons d'export -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $student->first_name }} {{ $student->last_name }}</h2>
            <p class="text-gray-600 mt-1">
                Matricule : <span class="font-mono font-semibold">{{ $student->matricule ?? 'N/A' }}</span> | 
                Classe : <span class="font-semibold text-primary">{{ $enrollment->schoolClass->name ?? 'N/A' }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.financial.export.student_detail.excel', ['studentId' => $student->id, 'school_year_id' => $schoolYearId]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📗 Export Excel
            </a>
            <a href="{{ route('app.financial.export.student_detail.pdf', ['studentId' => $student->id, 'school_year_id' => $schoolYearId]) }}" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📕 Export PDF
            </a>
        </div>
    </div>

    <!-- Cartes de Statistiques -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Dû</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($totalDue, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Payé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Reste à Payer</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($totalRemaining, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Taux de Paiement</p>
            <p class="text-2xl font-bold text-primary">{{ $paymentRate }}%</p>
        </div>
    </div>

    <!-- Tableau des Échéances -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">📅 Échéances (Installments)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">#</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Description</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Date Échéance</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($installments as $index => $installment)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 text-sm font-medium text-gray-800 capitalize">{{ $installment->type }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $installment->description ?? '-' }}</td>
                        <td class="py-3 px-4 text-right text-sm font-semibold text-gray-800">{{ number_format($installment->amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-right text-sm font-semibold text-green-700">{{ number_format($installment->paid_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($installment->status === 'paid')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Payé</span>
                            @elseif($installment->is_overdue)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">En retard</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-bold">En attente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">Aucune échéance enregistrée pour cet élève.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tableau de l'Historique des Paiements -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">💳 Historique des Paiements</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">#</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Mode</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Référence</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $index => $payment)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                        <td class="py-3 px-4 text-sm font-medium text-gray-800">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right text-sm font-semibold text-green-700">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-sm text-gray-600 capitalize">{{ $payment->payment_type ?? '-' }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600 capitalize">{{ $payment->payment_method ?? '-' }}</td>
                        <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $payment->reference ?? '-' }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500">Aucun paiement enregistré pour cet élève.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection