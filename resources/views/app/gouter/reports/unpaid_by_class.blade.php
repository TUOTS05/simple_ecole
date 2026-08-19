@extends('layouts.app')

@section('title', 'Rapport Goûter')
@section('page_title', 'Recouvrement Goûter par Classe')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" action="{{ route('gouter.reports.unpaid_by_class') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                <select name="class_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">Toutes les classes</option>
                    @foreach($allClasses as $class)
                        <option value="{{ $class->id }}" {{ $filterClassId == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                🔍 Filtrer
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Total attendu</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($globalStats->total_expected, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Total payé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($globalStats->total_paid, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Reste à recouvrer</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Taux de recouvrement</p>
            <p class="text-2xl font-bold text-primary">{{ $globalStats->recovery_rate }}%</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Élèves inscrits</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Attendu</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Reste</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Taux</th>
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
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">
                        Aucune inscription au goûter pour cette année scolaire.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
