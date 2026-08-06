@extends('layouts.app')

@section('title', 'Bulletins')
@section('page_title', 'Bulletins scolaires')

@section('content')

@if(session('success'))
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
    {{ session('success') }}
</div>
@endif

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Bulletins scolaires</h1>
        <p class="text-gray-600 mt-1">Gérez les compositions et bulletins des élèves</p>
    </div>
    <div class="flex gap-2">
        {{-- ✅ NOUVEAU : Bouton de téléchargement en masse --}}
        @php $hasFilter = request('period') || request('month') || request('quarter'); @endphp
        @if($hasFilter)
            <a href="{{ route('app.report-cards.bulk-download', request()->query()) }}"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition shadow flex items-center gap-2">
                📥 Télécharger tous les bulletins (PDF)
            </a>
        @endif
        
        <a href="{{ route('app.report-cards.create') }}"
            class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition shadow">
            + Nouveau Bulletin
        </a>
    </div>
</div>
<!-- Filtres avec basculement dynamique -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('app.report-cards.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
            <select name="period" id="filter_periodSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                <option value="">Toutes les périodes</option>
                <option value="Mensuel" {{ request('period') === 'Mensuel' ? 'selected' : '' }}>📅 Mensuel</option>
                <option value="Trimestriel" {{ request('period') === 'Trimestriel' ? 'selected' : '' }}>📊 Trimestriel</option>
            </select>
        </div>

        <!-- ✅ Champ Mois (Visible par défaut si Mensuel ou pas de filtre) -->
        <div id="filter_monthField" class="{{ request('period') === 'Trimestriel' ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
            <select name="month" id="filter_monthSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                <option value="">Tous les mois</option>
                <option value="Janvier" {{ request('month') === 'Janvier' ? 'selected' : '' }}>Janvier</option>
                <option value="Février" {{ request('month') === 'Février' ? 'selected' : '' }}>Février</option>
                <option value="Mars" {{ request('month') === 'Mars' ? 'selected' : '' }}>Mars</option>
                <option value="Avril" {{ request('month') === 'Avril' ? 'selected' : '' }}>Avril</option>
                <option value="Mai" {{ request('month') === 'Mai' ? 'selected' : '' }}>Mai</option>
                <option value="Juin" {{ request('month') === 'Juin' ? 'selected' : '' }}>Juin</option>
                <option value="Juillet" {{ request('month') === 'Juillet' ? 'selected' : '' }}>Juillet</option>
                <option value="Août" {{ request('month') === 'Août' ? 'selected' : '' }}>Août</option>
                <option value="Septembre" {{ request('month') === 'Septembre' ? 'selected' : '' }}>Septembre</option>
                <option value="Octobre" {{ request('month') === 'Octobre' ? 'selected' : '' }}>Octobre</option>
                <option value="Novembre" {{ request('month') === 'Novembre' ? 'selected' : '' }}>Novembre</option>
                <option value="Décembre" {{ request('month') === 'Décembre' ? 'selected' : '' }}>Décembre</option>
            </select>
        </div>

        <!-- ✅ Champ Trimestre (Caché par défaut si Mensuel ou pas de filtre) -->
        <div id="filter_quarterField" class="{{ request('period') === 'Mensuel' ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-gray-700 mb-2">Trimestre</label>
            <select name="quarter" id="filter_quarterSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                <option value="">Tous les trimestres</option>
                <option value="1" {{ request('quarter') == '1' ? 'selected' : '' }}>1er Trimestre</option>
                <option value="2" {{ request('quarter') == '2' ? 'selected' : '' }}>2ème Trimestre</option>
                <option value="3" {{ request('quarter') == '3' ? 'selected' : '' }}>3ème Trimestre</option>
            </select>
        </div>

        <div class="flex items-end space-x-2">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                🔍 Filtrer
            </button>
            <a href="{{ route('app.report-cards.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                Réinitialiser
            </a>
        </div>

    </form>
</div>

@php
    $hasFilter = request('period') || request('month') || request('quarter');
@endphp

@if($hasFilter)
    <!-- Tableau (s'affiche uniquement si une recherche est effectuée) -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Période</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Moyenne</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rang</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportCards as $card)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4 font-semibold">
                        {{ $card->student->last_name }} {{ $card->student->first_name }}
                    </td>
                    <td class="py-3 px-4 text-sm">{{ $card->schoolClass->name ?? 'N/A' }}</td>
                    <td class="py-3 px-4">
                        @if(strtolower($card->period) === 'mensuel')
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            Mensuel - {{ $card->month }} {{ $card->schoolYear->name ?? '' }}
                        </span>
                        @else
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                            {{ $card->quarter }}ème Trimestre
                        </span>
                        @endif
                    </td>
                    <td class="py-3 px-4 font-bold text-lg
                                @if($card->average >= 16) text-green-600
                                @elseif($card->average >= 14) text-blue-600
                                @elseif($card->average >= 12) text-yellow-600
                                @else text-red-600
                                @endif">
                        {{ number_format($card->average, 2) }}/20
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                            {{ $card->rank }} / {{ $card->total_students }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        {{ $card->created_at->format('d/m/Y') }}
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex space-x-2">
                            <a href="{{ route('app.report-cards.show', $card) }}"
                                class="text-blue-600 hover:text-blue-800 font-semibold text-sm" title="Voir">
                                👁️
                            </a>
                            <a href="{{ route('app.report-cards.edit', $card) }}"
                                class="text-yellow-600 hover:text-yellow-800 font-semibold text-sm" title="Modifier">
                                ✏️
                            </a>
                            <a href="{{ route('app.report-cards.pdf', $card) }}"
                                class="text-red-600 hover:text-red-800 font-semibold text-sm" title="Télécharger PDF">
                                📄
                            </a>
                            <form action="{{ route('app.report-cards.destroy', $card) }}" method="POST" class="inline"
                                onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce bulletin ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 text-sm" title="Supprimer">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-gray-500">
                        Aucun bulletin trouvé pour les critères sélectionnés.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($reportCards->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            {{ $reportCards->links() }}
        </div>
        @endif
    </div>
@else
    <!-- Message d'incitation à la recherche -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-8 rounded-lg text-center shadow-sm">
        <div class="text-blue-400 text-5xl mb-4">🔍</div>
        <h3 class="text-xl font-bold text-blue-800 mb-2">Recherche de bulletins</h3>
        <p class="text-blue-700 max-w-2xl mx-auto">
            Veuillez sélectionner au moins un critère (Période, Mois ou Trimestre) dans le formulaire ci-dessus, puis cliquer sur le bouton <strong>"Filtrer"</strong> pour afficher la liste des bulletins.
        </p>
    </div>
@endif

<!-- ✅ SCRIPT INFAILLIBLE POUR LE BASCULEMENT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('filter_periodSelect');
    const monthField = document.getElementById('filter_monthField');
    const quarterField = document.getElementById('filter_quarterField');
    const monthSelect = document.getElementById('filter_monthSelect');
    const quarterSelect = document.getElementById('filter_quarterSelect');

    function toggleFilterFields() {
        const periodValue = periodSelect.value;
        
        if (periodValue === 'Mensuel') {
            monthField.classList.remove('hidden');
            quarterField.classList.add('hidden');
            if (monthSelect) monthSelect.required = false; // Optionnel pour le filtre
            if (quarterSelect) quarterSelect.required = false;
        } else if (periodValue === 'Trimestriel') {
            monthField.classList.add('hidden');
            quarterField.classList.remove('hidden');
            if (monthSelect) monthSelect.required = false;
            if (quarterSelect) quarterSelect.required = false;
        } else {
            // Si "Toutes les périodes" est sélectionné, on affiche les deux
            monthField.classList.remove('hidden');
            quarterField.classList.remove('hidden');
        }
    }

    // Écouter le changement
    periodSelect.addEventListener('change', toggleFilterFields);

    // Exécuter au chargement pour s'aligner sur la valeur actuelle
    toggleFilterFields();
});
</script>

@endsection