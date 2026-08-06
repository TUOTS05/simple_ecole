@extends('layouts.app')

@section('title', 'Notes - ' . $class->name)
@section('page_title', 'Gestion des Notes : ' . $class->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h2>
            <p class="text-gray-500">Cycle : {{ ucfirst($class->cycle) }} | Niveau : {{ $class->level }}</p>
        </div>
        <a href="{{ route('teacher.class.details', ['id' => $class->id]) }}" class="text-primary hover:text-primary-dark font-semibold">
            ← Retour à la classe
        </a>
    </div>

    <!-- Formulaire de sélection -->
    <form method="GET" action="{{ route('teacher.grades.index', $class->id) }}" class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center border-b pb-3">
            <i class="fas fa-filter text-primary mr-2"></i> 1. Choisir la matière et la période
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <!-- Matière -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Matière *</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- Sélectionner --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Période -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Période *</label>
                <select name="period" id="periodSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="Trimestriel" {{ $selectedPeriod == 'Trimestriel' ? 'selected' : '' }}>Trimestriel</option>
                    <option value="Mensuel" {{ $selectedPeriod == 'Mensuel' ? 'selected' : '' }}>Mensuel</option>
                </select>
            </div>

            <!-- Trimestre (Caché si Mensuel) -->
            <div id="quarterField" class="{{ $selectedPeriod == 'Mensuel' ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                <select name="quarter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>1er Trimestre</option>
                    <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>2ème Trimestre</option>
                    <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>3ème Trimestre</option>
                </select>
            </div>

            <!-- Mois (Caché si Trimestriel) - AMÉLIORÉ EN SELECT -->
            <div id="monthField" class="{{ $selectedPeriod == 'Trimestriel' ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                    <option value="">-- Sélectionner un mois --</option>
                    @php
                        $moisListe = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                    @endphp
                    @foreach($moisListe as $m)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bouton -->
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-primary-dark transition flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i> Afficher
                </button>
            </div>
        </div>
    </form>

    <!-- Tableau de saisie (s'affiche uniquement si une matière est choisie) -->
    @if($selectedSubjectId)
        <form action="{{ route('teacher.grades.store', $class->id) }}" method="POST">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">
            <input type="hidden" name="period" value="{{ $selectedPeriod }}">
            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="max_score" value="20">

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-edit text-accent mr-2"></i> 
                        2. Saisie / Modification des notes
                    </h3>
                    <span class="text-sm text-gray-500">{{ $students->count() }} élèves</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
                            <tr>
                                <th class="px-6 py-4 font-semibold w-1/4">Matricule</th>
                                <th class="px-6 py-4 font-semibold w-1/3">Nom et Prénom</th>
                                <th class="px-6 py-4 font-semibold text-center w-1/6">Note / 20</th>
                                <th class="px-6 py-4 font-semibold w-1/4">Appréciation / Remarque</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($students as $student)
                                @php
                                    $grade = $existingGrades->get($student->id);
                                    $currentScore = $grade ? $grade->score : '';
                                    $currentRemarks = $grade ? $grade->remarks : '';
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $student->matricule ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <input type="number" name="grades[{{ $student->id }}][score]" value="{{ $currentScore }}" 
                                               step="0.5" min="0" max="20" 
                                               class="w-20 px-2 py-1 border border-gray-300 rounded text-center font-bold focus:ring-2 focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="grades[{{ $student->id }}][remarks]" value="{{ $currentRemarks }}" 
                                               placeholder="Ex: Bon effort" 
                                               class="w-full px-3 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-primary">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-primary to-primary-dark text-white rounded-lg font-bold shadow-lg transition transform hover:scale-105">
                        💾 Enregistrer les notes
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center">
            <i class="fas fa-arrow-up text-blue-400 text-3xl mb-3"></i>
            <p class="text-blue-800 font-semibold">Veuillez sélectionner une matière et une période ci-dessus pour afficher le tableau des élèves.</p>
        </div>
    @endif
</div>

<!-- ✅ SCRIPT INFAILLIBLE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('periodSelect');
    const monthField = document.getElementById('monthField');
    const quarterField = document.getElementById('quarterField');

    // Fonction de basculement
    function togglePeriodFields() {
        if (periodSelect.value === 'Mensuel') {
            monthField.classList.remove('hidden');
            quarterField.classList.add('hidden');
        } else {
            monthField.classList.add('hidden');
            quarterField.classList.remove('hidden');
        }
    }

    // Écouter le changement
    periodSelect.addEventListener('change', togglePeriodFields);

    // Exécuter au chargement pour s'aligner sur la valeur sélectionnée par Blade
    togglePeriodFields();
});
</script>
@endsection