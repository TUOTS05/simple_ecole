@extends('layouts.app')

@section('title', 'Tarifs Cantine')
@section('page_title', 'Tarifs de la Cantine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Filtres et Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <!-- ✅ CORRECTION ICI : canteen.rates.index au lieu de app.canteen.rates.index -->
        <form method="GET" action="{{ route('canteen.rates.index') }}" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                🔍 Filtrer
            </button>
        </form>
        
        <!-- ✅ CORRECTION ICI : canteen.rates.create -->
        <a href="{{ route('canteen.rates.create', ['school_year_id' => $schoolYearId]) }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
            + Nouveau Tarif
        </a>
    </div>

    <!-- Tableau des Tarifs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Tarif Mensuel</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Nombre de Mois</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total Annuel</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Période</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rates as $rate)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-medium text-gray-800">{{ $rate->schoolClass->name }}</td>
                    <td class="py-3 px-4 text-right text-gray-800">{{ number_format($rate->monthly_rate, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ $rate->months_count }} mois</td>
                    <td class="py-3 px-4 text-right font-bold text-primary">{{ number_format($rate->total_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        {{ \Carbon\Carbon::parse($rate->start_month . '-01')->format('M Y') }} - 
                        {{ \Carbon\Carbon::parse($rate->end_month . '-01')->format('M Y') }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <!-- ✅ CORRECTIONS ICI : canteen.rates.edit et canteen.rates.destroy -->
                        <a href="{{ route('canteen.rates.edit', $rate->id) }}" class="text-primary hover:text-primary-dark mr-3">✏️ Modifier</a>
                        <form action="{{ route('canteen.rates.destroy', $rate->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce tarif ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">
                        Aucun tarif défini pour cette année scolaire.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection