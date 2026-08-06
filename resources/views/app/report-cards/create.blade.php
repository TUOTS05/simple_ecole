@extends('layouts.app')

@section('title', 'Saisie des notes')
@section('page_title', 'Saisie des notes')

@section('content')

<div class="max-w-7xl mx-auto">

    <!-- Sélecteurs -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-primary">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="mr-2">⚙️</span> 1. Sélectionner la classe et la période
        </h2>
        <form method="GET" action="{{ route('app.report-cards.create') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe *</label>
                <select name="class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                        {{ $class->name }} ({{ $class->cycle }} - {{ $class->level }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Période *</label>
                <select name="period" id="admin_periodSelect" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="mensuel" {{ (old('period', $period) === 'mensuel') ? 'selected' : '' }}>📅 Mensuel</option>
                    <option value="trimestriel" {{ (old('period', $period) === 'trimestriel') ? 'selected' : '' }}>📊 Trimestriel</option>
                </select>
            </div>

            <!-- ✅ Champ Mois (Affiché par défaut si mensuel, sinon hidden) -->
            <div id="admin_monthField" class="{{ (old('period', $period) === 'trimestriel') ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois *</label>
                <select name="month" id="admin_monthSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- Sélectionner --</option>
                    @php
                        $moisListe = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                    @endphp
                    @foreach($moisListe as $m)
                        <option value="{{ $m }}" {{ old('month', ucfirst($month)) === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <!-- ✅ Champ Trimestre (Caché par défaut si mensuel) -->
            <div id="admin_quarterField" class="{{ (old('period', $period) === 'mensuel') ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-2">Trimestre *</label>
                <select name="quarter" id="admin_quarterSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="1" {{ old('quarter', $quarter) == 1 ? 'selected' : '' }}>1er Trimestre</option>
                    <option value="2" {{ old('quarter', $quarter) == 2 ? 'selected' : '' }}>2ème Trimestre</option>
                    <option value="3" {{ old('quarter', $quarter) == 3 ? 'selected' : '' }}>3ème Trimestre</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition flex items-center justify-center">
                    <span class="mr-2">🔍</span> Charger
                </button>
            </div>

        </form>
    </div>

    <!-- Formulaire de saisie -->
    @if($students->count() > 0 && $subjects->count() > 0)
    <form action="{{ route('app.report-cards.store') }}" method="POST">
        @csrf
        <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
        <!-- On force la majuscule pour correspondre à la logique du contrôleur -->
        <input type="hidden" name="period" value="{{ ucfirst(old('period', $period)) }}">
        <input type="hidden" name="month" value="{{ ucfirst(old('month', $month)) }}">
        <input type="hidden" name="quarter" value="{{ old('quarter', $quarter) }}">

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <span class="mr-2">✏️</span> 2. Saisir ou modifier les notes
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $students->count() }} élèves - {{ $subjects->count() }} matières
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-3 py-3 text-left text-sm font-semibold text-gray-700 sticky left-0 bg-gray-100 z-10" style="width: 25%;">
                                Élève
                            </th>
                            @foreach($subjects as $subject)
                            <th class="border border-gray-300 px-3 py-3 text-center text-sm font-semibold text-gray-700 min-w-[120px]">
                                <div class="flex flex-col">
                                    <span>{{ $subject->name }}</span>
                                    <span class="text-xs text-gray-500">Coeff: {{ $subject->coefficient ?? 1 }}</span>
                                    <span class="text-xs text-blue-600 font-bold">/{{ $subject->max_score ?? 20 }}</span>
                                </div>
                                <input type="hidden" name="coefficients[{{ $subject->id }}]" value="{{ $subject->coefficient ?? 1 }}">
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-3 py-3 font-semibold sticky left-0 bg-white z-10">
                                <div class="text-sm">
                                    <div>{{ $student->last_name }} {{ $student->first_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->matricule ?? 'N/A' }}</div>
                                </div>
                            </td>
                            @foreach($subjects as $subject)
                            <td class="border border-gray-300 px-2 py-3">
                                <div class="space-y-2">
                                    @php
                                        $existingNote = $existingGrades[$student->id][$subject->id] ?? null;
                                        $currentScore = $existingNote ? $existingNote->score : '';
                                        $currentRemarks = $existingNote ? $existingNote->remarks : '';
                                        $maxScore = $subject->max_score ?? $existingNote->max_score ?? 20;
                                    @endphp
                                    
                                    <input type="number"
                                        name="grades[{{ $student->id }}][{{ $subject->id }}][score]"
                                        value="{{ $currentScore }}"
                                        placeholder="Note"
                                        min="0"
                                        max="{{ $maxScore }}"
                                        step="0.25"
                                        class="w-full px-2 py-1 border border-gray-300 rounded text-center text-sm focus:ring-2 focus:ring-primary focus:border-primary font-bold {{ $currentScore !== '' ? 'bg-blue-50' : '' }}">
                                    
                                    <input type="hidden"
                                        name="grades[{{ $student->id }}][{{ $subject->id }}][max_score]"
                                        value="{{ $maxScore }}">
                                    
                                    <input type="text"
                                        name="grades[{{ $student->id }}][{{ $subject->id }}][remarks]"
                                        value="{{ $currentRemarks }}"
                                        placeholder="Appréciation"
                                        class="w-full px-2 py-1 border border-gray-300 rounded text-xs text-center">
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end space-x-4 mb-6">
            <button type="button" onclick="fillAllScores(0)" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                Mettre à 0
            </button>
            <button type="submit" class="bg-gradient-to-r from-primary to-primary-dark text-white px-8 py-3 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                💾 Enregistrer et Générer les Bulletins
            </button>
        </div>
    </form>

    @elseif($selectedClassId)
        @if($subjects->count() == 0)
        <div class="bg-orange-50 border-l-4 border-orange-400 p-6 rounded-lg mb-6">
            <p class="text-orange-800 text-lg font-semibold">⚠️ Aucune matière configurée pour ce niveau</p>
            <p class="text-orange-700 mt-2">
                La classe <strong>{{ $classes->find($selectedClassId)->name }}</strong> est de niveau
                <span class="font-mono bg-orange-100 px-2 py-1 rounded">{{ $classes->find($selectedClassId)->level }}</span>
                (Cycle: {{ $classes->find($selectedClassId)->cycle }}).
            </p>
            <p class="text-orange-700 mt-1">
                → Allez dans <a href="{{ route('app.subjects.create') }}" class="underline font-bold">📚 Matières</a> et configurez les matières exactement pour ce niveau.
            </p>
        </div>
        @endif
        @if($students->count() == 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
            <p class="text-yellow-800 text-lg">⚠️ Aucun élève actif trouvé dans cette classe.</p>
        </div>
        @endif
    @endif

</div>

<!-- ✅ SCRIPT INFAILLIBLE POUR LE BASCULEMENT PÉRIODE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('admin_periodSelect');
    const monthField = document.getElementById('admin_monthField');
    const quarterField = document.getElementById('admin_quarterField');

    function toggleAdminPeriod() {
        if (periodSelect.value === 'mensuel') {
            monthField.classList.remove('hidden');
            quarterField.classList.add('hidden');
        } else {
            monthField.classList.add('hidden');
            quarterField.classList.remove('hidden');
        }
    }

    // Écouter le changement de sélection
    periodSelect.addEventListener('change', toggleAdminPeriod);

    // Exécuter au chargement pour s'aligner sur la valeur actuelle (ou old())
    toggleAdminPeriod();
});

function fillAllScores(value) {
    if (confirm('Voulez-vous vraiment mettre toutes les notes à ' + value + ' ?')) {
        document.querySelectorAll('input[name*="[score]"]').forEach(input => {
            input.value = value;
        });
    }
}
</script>

@endsection