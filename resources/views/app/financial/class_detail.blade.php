@extends('layouts.app')

@section('title', 'Détail - ' . $class->name)
@section('page_title', 'Détail des paiements - ' . $class->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Retour -->
    <div>
        <a href="{{ route('app.financial.unpaid_by_class', ['school_year_id' => $schoolYearId]) }}" 
           class="text-primary hover:text-primary-dark font-semibold flex items-center gap-2">
            ← Retour aux impayés par classe
        </a>
    </div>

        <!-- En-tête avec boutons d'export -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h2>
            <p class="text-gray-600 mt-1">Détail des paiements par élève (basé sur les échéances)</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.financial.export.class_detail.excel', ['classId' => $class->id, 'school_year_id' => $schoolYearId]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📗 Export Excel
            </a>
            <a href="{{ route('app.financial.export.class_detail.pdf', ['classId' => $class->id, 'school_year_id' => $schoolYearId]) }}" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📕 Export PDF
            </a>
        </div>
    </div>

    <!-- Tableau des Élèves -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matricule</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom et Prénom</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total Dû</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Reste</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-primary">Taux</th>
                    </tr>
                </thead>
                                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $student)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $student->matricule ?? 'N/A' }}</td>
                        
                        <!-- ✅ LIEN CLIQUABLE VERS LE DÉTAIL DE L'ÉLÈVE (Vue Web) -->
                        <td class="py-3 px-4 font-medium text-gray-800">
                            <a href="{{ route('app.financial.student_detail', ['studentId' => $student->student_id, 'school_year_id' => $schoolYearId]) }}" 
                               class="text-primary hover:text-primary-dark hover:underline flex items-center gap-1 transition">
                                {{ strtoupper($student->last_name) }} {{ ucfirst($student->first_name) }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </td>

                        <td class="py-3 px-4 text-right text-gray-800 font-semibold">
                            {{ number_format($student->total_du, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="py-3 px-4 text-right text-green-700 font-semibold">
                            {{ number_format($student->total_paye, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="py-3 px-4 text-right text-red-700 font-semibold">
                            {{ number_format($student->total_reste, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($student->payment_rate >= 100)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">✓ Soldé</span>
                            @elseif($student->payment_rate >= 50)
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs font-bold">{{ $student->payment_rate }}%</span>
                            @elseif($student->payment_rate > 0)
                                <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold">{{ $student->payment_rate }}%</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">0%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-500">
                            <div class="text-4xl mb-3">📭</div>
                            <p class="font-medium">Aucun élève ou aucune donnée financière pour cette classe.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection