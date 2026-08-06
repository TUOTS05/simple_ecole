@extends('layouts.app')

@section('title', 'Saisie des notes')
@section('page_title', 'Nouvelle Saisie de Notes')

@section('content')

<!-- ✅ TEST VISUEL CRITIQUE : Si vous voyez ce texte, le bon fichier est chargé -->
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 font-bold text-xl">
    ✅ FICHIER create.blade.php CHARGÉ AVEC SUCCÈS !
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <form action="{{ route('teacher.grades.store', $class->id) }}" method="POST">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border-l-4 border-primary">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $subject->name }} - {{ $class->name }}</h2>
                    <p class="text-sm text-gray-500">Saisie des notes pour la période sélectionnée</p>
                </div>
                <a href="{{ route('teacher.grades.index', $class->id) }}" class="text-gray-500 hover:text-gray-700 text-sm font-semibold">
                    ← Annuler
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période *</label>
                    <select name="period" id="periodSelect" onchange="window.togglePeriod(this.value)" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="Mensuel">Mensuel</option>
                        <option value="Trimestriel">Trimestriel</option>
                    </select>
                </div>

                <div id="monthField">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mois *</label>
                    <select name="month" id="monthSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="">-- Sélectionner un mois --</option>
                        <option value="Janvier">Janvier</option>
                        <option value="Février">Février</option>
                        <option value="Mars">Mars</option>
                        <option value="Avril">Avril</option>
                        <option value="Mai">Mai</option>
                        <option value="Juin">Juin</option>
                        <option value="Juillet">Juillet</option>
                        <option value="Août">Août</option>
                        <option value="Septembre">Septembre</option>
                        <option value="Octobre">Octobre</option>
                        <option value="Novembre">Novembre</option>
                        <option value="Décembre">Décembre</option>
                    </select>
                </div>

                <div id="quarterField" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre *</label>
                    <select name="quarter" id="quarterSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                        <option value="1">1er Trimestre</option>
                        <option value="2">2ème Trimestre</option>
                        <option value="3">3ème Trimestre</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Note maximale *</label>
                <input type="number" name="max_score" id="maxScoreInput" required value="20" step="0.5" min="1" class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
                        <tr>
                            <th class="text-left py-3 px-4 w-1/3">Élève</th>
                            <th class="text-center py-3 px-4 w-1/6">Note</th>
                            <th class="text-left py-3 px-4">Appréciation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($students as $student)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="number" name="grades[{{ $student->id }}][score]" step="0.5" min="0" class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center">
                            </td>
                            <td class="py-3 px-4">
                                <input type="text" name="grades[{{ $student->id }}][remarks]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-primary-dark transition">
                Enregistrer les notes
            </button>
        </div>
    </form>
</div>

<script>
    console.log("🚀 SCRIPT CHARGÉ AVEC SUCCÈS !");
    
    window.togglePeriod = function(val) {
        console.log("🔄 Changement de période détecté : " + val);
        const mField = document.getElementById('monthField');
        const qField = document.getElementById('quarterField');
        const mSelect = document.getElementById('monthSelect');
        const qSelect = document.getElementById('quarterSelect');

        if (val === 'Mensuel') {
            mField.style.display = 'block';
            qField.style.display = 'none';
            mSelect.required = true;
            qSelect.required = false;
        } else {
            mField.style.display = 'none';
            qField.style.display = 'block';
            mSelect.required = false;
            qSelect.required = true;
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const pSelect = document.getElementById('periodSelect');
        if (pSelect) {
            window.togglePeriod(pSelect.value);
        }
    });
</script>
@endsection