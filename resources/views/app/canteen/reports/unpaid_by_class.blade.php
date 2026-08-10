@extends('layouts.app')

@section('title', 'Rapport des Impayés Cantine')
@section('page_title', 'Rapport des Impayés Cantine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Filtres : Année + Classe + Mois -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" action="{{ route('canteen.reports.unpaid_by_class') }}" class="flex flex-col md:flex-row gap-4 items-end">
            
            <!-- Filtre Année -->
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

            <!-- ✅ NOUVEAU : Filtre Classe -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrer par Classe</label>
                <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">Toutes les classes</option>
                    @foreach($allClasses as $class)
                        <option value="{{ $class->id }}" {{ $filterClassId == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ✅ NOUVEAU : Filtre Mois -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtrer par Mois</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($availableMonths as $value => $label)
                        <option value="{{ $value }}" {{ $filterMonth == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition h-[42px]">
                🔍 Filtrer
            </button>
        </form>
    </div>

    <!-- Titre Dynamique selon les filtres -->
    <div class="flex items-center gap-2">
        <h2 class="text-xl font-bold text-gray-800">
            📊 Résultat : 
            @if($filterMonth && $filterClassId)
                <span class="text-primary">Classe spécifique</span> pour <span class="text-primary">{{ $availableMonths[$filterMonth] }}</span>
            @elseif($filterMonth)
                <span class="text-primary">Toutes les classes</span> pour <span class="text-primary">{{ $availableMonths[$filterMonth] }}</span>
            @elseif($filterClassId)
                <span class="text-primary">Classe spécifique</span> (Vue Globale)
            @else
                <span class="text-primary">Toutes les classes</span> (Vue Globale)
            @endif
        </h2>
    </div>

    <!-- Statistiques Globales (adaptées au filtre) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Attendu</div>
            <div class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($globalStats->total_expected, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Payé</div>
            <div class="text-2xl font-bold text-green-600 mt-2">{{ number_format($globalStats->total_paid, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Total Impayé</div>
            <div class="text-2xl font-bold text-red-600 mt-2">{{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <div class="text-sm font-medium text-gray-500">Taux de Recouvrement</div>
            <div class="text-2xl font-bold text-primary mt-2">{{ $globalStats->recovery_rate }}%</div>
        </div>
    </div>

    <!-- Tableau des Classes -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">
                @if($filterMonth) Détail des Échéances du Mois @else Détail Global par Classe @endif
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Élèves Inscrits</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total Attendu</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Total Payé</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Total Impayé</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Taux</th>
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
                            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $class->recovery_rate >= 80 ? 'bg-green-100 text-green-700' : ($class->recovery_rate >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ $class->recovery_rate }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('canteen.reports.class_detail', ['classId' => $class->class_id, 'school_year_id' => $schoolYearId]) }}" 
                               class="text-primary hover:text-primary-dark text-sm font-medium">
                                👁️ Voir détails
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            Aucune donnée disponible pour ces critères de filtrage.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection