@extends('layouts.app')

@section('title', 'Détail des Paiements - ' . $class->name)
@section('page_title', 'Détail des Paiements : ' . $class->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" action="{{ route('canteen.reports.class_detail', ['classId' => $class->id]) }}" class="flex flex-col md:flex-row gap-4 items-end">
            <input type="hidden" name="classId" value="{{ $class->id }}">
            
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ✅ NOUVEAU : Filtre par Mois -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrer par Mois</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($availableMonths as $value => $label)
                        <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                🔍 Filtrer
            </button>
            
            <a href="{{ route('canteen.reports.unpaid_by_class', ['school_year_id' => $schoolYearId]) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                ← Retour
            </a>
        </form>
    </div>

    <!-- Résumé de la classe -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Attendu (Classe)</div>
            <div class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($students->sum('total_du'), 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Payé (Classe)</div>
            <div class="text-2xl font-bold text-green-600 mt-2">{{ number_format($students->sum('total_paye'), 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Taux de Recouvrement</div>
            @php
                $totalDue = $students->sum('total_du');
                $totalPaid = $students->sum('total_paye');
                $rate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0;
            @endphp
            <div class="text-2xl font-bold text-primary mt-2">{{ $rate }}%</div>
        </div>
    </div>

    <!-- Tableau des Élèves -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">
                📋 Liste des Élèves 
                @if($selectedMonth)
                    <span class="text-sm font-normal text-gray-600 ml-2">(Statut pour {{ $availableMonths[$selectedMonth] ?? $selectedMonth }})</span>
                @else
                    <span class="text-sm font-normal text-gray-600 ml-2">(Statut Global)</span>
                @endif
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matricule</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom et Prénom</th>
                        
                        @if($selectedMonth)
                            <!-- Colonnes spécifiques au mois sélectionné -->
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut du Mois</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Montant Mois</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé ce Mois</th>
                        @else
                            <!-- Colonnes globales -->
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total Dû</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Total Payé</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Reste</th>
                        @endif
                        
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Taux Global</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $student)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $student->matricule }}</td>
                        <td class="py-3 px-4 font-medium text-gray-800">
                            {{ strtoupper($student->last_name) }} {{ $student->first_name }}
                        </td>
                        
                        @if($selectedMonth)
                            <td class="py-3 px-4 text-center">
                                @if($student->month_status === 'paid')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Payé</span>
                                @elseif($student->month_status === 'partial')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">Partiel</span>
                                @elseif($student->month_status === 'pending')
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Impayé</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">Non programmé</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right text-gray-800">{{ number_format($student->month_due_amount, 0, ',', ' ') }} FCFA</td>
                            <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($student->month_paid_amount, 0, ',', ' ') }} FCFA</td>
                        @else
                            <td class="py-3 px-4 text-right text-gray-800">{{ number_format($student->total_du, 0, ',', ' ') }} FCFA</td>
                            <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($student->total_paye, 0, ',', ' ') }} FCFA</td>
                            <td class="py-3 px-4 text-right text-red-700 font-semibold">{{ number_format($student->total_reste, 0, ',', ' ') }} FCFA</td>
                        @endif

                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $student->payment_rate >= 80 ? 'bg-green-100 text-green-700' : ($student->payment_rate >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ $student->payment_rate }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('canteen.reports.student_detail', ['studentId' => $student->id, 'school_year_id' => $schoolYearId]) }}" 
                               class="text-primary hover:text-primary-dark text-sm font-medium">
                                👁️ Détail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-500">
                            Aucun élève inscrit à la cantine dans cette classe pour cette année.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection