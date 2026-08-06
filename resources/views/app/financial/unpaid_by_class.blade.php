@extends('layouts.app')

@section('title', 'Impayés par classe')
@section('page_title', 'États Financiers - Impayés')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" action="{{ route('app.financial.unpaid_by_class') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Année Scolaire</label>
                <select name="school_year_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition shadow">
                    🔍 Filtrer
                </button>
            </div>
        </form>
    </div>
        <!-- Boutons d'Export -->
    <div class="flex justify-end gap-2">
        <a href="{{ route('app.financial.export.unpaid_by_class.excel', ['school_year_id' => $schoolYearId]) }}" 
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
            📗 Export Excel
        </a>
        <a href="{{ route('app.financial.export.unpaid_by_class.pdf', ['school_year_id' => $schoolYearId]) }}" 
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
            📕 Export PDF
        </a>
    </div>

    <!-- Cartes de Statistiques Globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Attendu</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($globalStats->total_expected, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Encaissé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($globalStats->total_paid, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Total Impayé</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Taux de Recouvrement</p>
            <p class="text-2xl font-bold text-primary">{{ $globalStats->recovery_rate }}%</p>
        </div>
    </div>

    <!-- Tableau des Impayés par Classe -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                💰 Impayés par Classe
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Élèves</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total Attendu</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Total Payé</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Total Impayé</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-primary">Recouvrement</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($classes as $class)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-medium text-gray-800">{{ $class->class_name }}</td>
                        <td class="py-3 px-4 text-center text-gray-600">{{ $class->total_students }}</td>
                        <td class="py-3 px-4 text-right text-gray-800">{{ number_format($class->total_expected, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($class->total_paid, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-right text-red-700 font-semibold">{{ number_format($class->total_unpaid, 0, ',', ' ') }} FCFA</td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: {{ $class->recovery_rate }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">{{ $class->recovery_rate }}%</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('app.financial.class_detail', ['classId' => $class->class_id, 'school_year_id' => $schoolYearId]) }}" 
                               class="text-primary hover:text-primary-dark font-semibold text-sm">
                                Voir détail →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            <div class="text-4xl mb-3">📭</div>
                            <p class="font-medium">Aucune donnée financière pour cette année scolaire.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection