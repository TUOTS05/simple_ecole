@extends('layouts.app')

@section('title', 'Bulletin - ' . $reportCard->student->first_name . ' ' . $reportCard->student->last_name)
@section('page_title', 'Bulletin scolaire')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-6 flex justify-between items-center no-print">
        <a href="{{ route('app.report-cards.index') }}" class="text-primary hover:text-primary-dark font-semibold flex items-center">
            ← Retour à la liste
        </a>
        <div class="flex space-x-3">
            <a href="{{ route('app.report-cards.edit', $reportCard) }}"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-semibold transition flex items-center">
                ✏️ Modifier
            </a>
            <a href="{{ route('app.report-cards.pdf', $reportCard) }}"
                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold transition flex items-center">
                📄 Télécharger PDF
            </a>
            <button onclick="window.print()" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition flex items-center">
                🖨️ Imprimer
            </button>
        </div>
    </div>

    <!-- Bulletin -->
    <div class="bg-white rounded-lg shadow-lg p-8 border-2 border-blue-300" id="reportCard">

        <!-- En-tête -->
        <div class="text-center border-b-2 border-blue-300 pb-6 mb-6">
            <h1 class="text-3xl font-bold text-blue-800 mb-2">
                {{ session('current_school')->name ?? 'ÉTABLISSEMENT SCOLAIRE' }}
            </h1>
            <p class="text-gray-600 mb-4">
                Année scolaire : <strong>{{ $reportCard->schoolYear->name ?? 'Non spécifiée' }}</strong>
            </p>
            
            {{-- ✅ CORRECTION 1 : Comparaison insensible à la casse et affichage direct du mois --}}
            @php
                $periodLower = strtolower($reportCard->period);
            @endphp
            
            <h2 class="text-2xl font-bold text-blue-800">
                BULLETIN DE COMPOSITION {{ $periodLower === 'mensuel' ? 'MENSUELLE' : 'TRIMESTRIELLE' }}
            </h2>
            @if($periodLower === 'mensuel')
                <p class="text-xl text-blue-600 mt-2">
                    Mois de : <span class="font-bold">{{ $reportCard->month }} {{ $reportCard->schoolYear->name ?? '' }}</span>
                </p>
            @else
                <p class="text-xl text-blue-600 mt-2">
                    {{ $reportCard->quarter }}ème Trimestre
                </p>
            @endif
        </div>

        <!-- Informations élève -->
        <div class="grid grid-cols-3 gap-4 mb-6 border-b border-gray-300 pb-4">
            <div>
                <p class="text-sm text-gray-600 uppercase">Nom et Prénoms</p>
                <p class="font-bold text-lg">
                    {{ strtoupper($reportCard->student->last_name) }} {{ $reportCard->student->first_name }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 uppercase">Classe</p>
                <p class="font-bold text-lg">{{ $reportCard->schoolClass->name ?? 'Non assignée' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 uppercase">Matricule</p>
                <p class="font-bold text-lg font-mono">{{ $reportCard->student->matricule ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Tableau des notes -->
        <table class="w-full border-2 border-blue-300 mb-6">
            <thead class="bg-blue-50">
                <tr>
                    <th class="border border-blue-300 px-4 py-3 text-left text-sm font-bold text-blue-800" style="width: 35%;">MATIÈRES</th>
                    <th class="border border-blue-300 px-4 py-3 text-center text-sm font-bold text-blue-800" style="width: 10%;">COEF</th>
                    <th class="border border-blue-300 px-4 py-3 text-center text-sm font-bold text-blue-800" style="width: 15%;">NOTE /20</th>
                    <th class="border border-blue-300 px-4 py-3 text-left text-sm font-bold text-blue-800" style="width: 40%;">APPRÉCIATION</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalWeightedScore = 0;
                    $totalCoefficients = 0;
                    // ✅ CORRECTION 2 : Utiliser $allSubjects pour afficher TOUTES les matières
                    $subjectsToDisplay = $allSubjects ?? $reportCard->grades->pluck('subject')->unique('id');
                @endphp

                @foreach($subjectsToDisplay as $subject)
                    @php
                        $grade = $reportCard->grades->firstWhere('subject_id', $subject->id);
                        $coefficient = $grade->coefficient_used ?? $subject->coefficient ?? 1;
                        
                        if ($grade) {
                            $maxScore = $subject->max_score ?? $grade->max_score ?? 20;
                            $scoreOutOf20 = ($grade->score / $maxScore) * 20;
                            
                            // ✅ CORRECTION 4 : Calcul pondéré par les coefficients
                            $totalWeightedScore += ($scoreOutOf20 * $coefficient);
                            $totalCoefficients += $coefficient;
                            
                            if ($scoreOutOf20 >= 18) $appreciation = 'Excellent';
                            elseif ($scoreOutOf20 >= 16) $appreciation = 'Très Bien';
                            elseif ($scoreOutOf20 >= 14) $appreciation = 'Bien';
                            elseif ($scoreOutOf20 >= 12) $appreciation = 'Assez Bien';
                            elseif ($scoreOutOf20 >= 10) $appreciation = 'Passable';
                            else $appreciation = 'Insuffisant';
                            
                            $displayAppreciation = $grade->remarks ?: $appreciation;
                            $hasGrade = true;
                        } else {
                            $scoreOutOf20 = null;
                            $displayAppreciation = '-';
                            $hasGrade = false;
                        }
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="border border-blue-300 px-4 py-3 font-semibold">
                            {{ $subject->name }}
                        </td>
                        <td class="border border-blue-300 px-4 py-3 text-center">
                            {{ $coefficient }}
                        </td>
                        <td class="border border-blue-300 px-4 py-3 text-center font-bold {{ !$hasGrade ? 'text-gray-400 italic' : '' }}">
                            @if($hasGrade)
                                {{ number_format($scoreOutOf20, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="border border-blue-300 px-4 py-3 text-sm italic">
                            {{ $displayAppreciation }}
                        </td>
                    </tr>
                @endforeach

                <!-- ✅ CORRECTION 3 : Une seule ligne propre pour la Moyenne Générale (suppression des doublons) -->
                <tr class="bg-blue-50 font-bold">
                    <td class="border border-blue-300 px-4 py-3" colspan="2" style="text-align: right; padding-right: 15px; text-transform: uppercase;">
                        Moyenne Générale
                    </td>
                    <td class="border border-blue-300 px-4 py-3 text-center text-lg text-blue-800">
                        @if($totalCoefficients > 0)
                            {{ number_format($totalWeightedScore / $totalCoefficients, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="border border-blue-300 px-4 py-3 text-sm">
                        @php
                            $avg = $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : 0;
                            if ($avg >= 16) echo 'Très Bien';
                            elseif ($avg >= 14) echo 'Bien';
                            elseif ($avg >= 12) echo 'Assez Bien';
                            elseif ($avg >= 10) echo 'Passable';
                            elseif ($avg > 0) echo 'Insuffisant';
                            else echo '-';
                        @endphp
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Résumé -->
        <div class="grid grid-cols-3 gap-6 mb-6 border-b-2 border-blue-300 pb-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-2 uppercase">Classement</p>
                <p class="text-3xl font-bold text-blue-800">{{ $reportCard->rank }}<span class="text-lg text-gray-500"> / {{ $reportCard->total_students }}</span></p>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-2 uppercase">Effectif</p>
                <p class="text-3xl font-bold text-blue-800">{{ $reportCard->total_students }}</p>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-2 uppercase">Mention</p>
                <p class="text-2xl font-bold text-blue-800">
                    @php
                        $avg = $totalCoefficients > 0 ? $totalWeightedScore / $totalCoefficients : 0;
                        if ($avg >= 16) echo 'Très Bien';
                        elseif ($avg >= 14) echo 'Bien';
                        elseif ($avg >= 12) echo 'Assez Bien';
                        elseif ($avg >= 10) echo 'Passable';
                        else echo '-';
                    @endphp
                </p>
            </div>
        </div>

        <!-- Appréciations et signatures -->
        <div class="grid grid-cols-2 gap-6">
            <div class="border-2 border-blue-300 p-4 rounded-lg">
                <p class="text-sm font-bold text-blue-800 mb-2 text-center uppercase">Appréciation du Conseil de Classe</p>
                <div class="h-32 border border-blue-200 rounded p-3 bg-white">
                    @if($reportCard->teacher_comment)
                        <p class="italic">{{ $reportCard->teacher_comment }}</p>
                    @else
                        <p class="italic text-gray-400">Aucune appréciation globale renseignée.</p>
                    @endif
                </div>
            </div>

            <div class="border-2 border-blue-300 p-4 rounded-lg">
                <p class="text-sm font-bold text-blue-800 mb-2 text-center uppercase">Visa et Cachet du Directeur</p>
                <div class="h-32 border border-blue-200 rounded p-3 bg-white flex items-center justify-center">
                    @if($reportCard->director_signed ?? false)
                        <div class="text-center">
                            <p class="font-bold text-lg text-green-600">✓ Validé</p>
                            <p class="text-sm text-gray-600">Le Directeur</p>
                        </div>
                    @else
                        <p class="text-gray-400 italic">En attente de validation</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Visa des parents -->
        <div class="mt-6 border-2 border-blue-300 p-4 rounded-lg">
            <p class="text-sm font-bold text-blue-800 mb-2 text-center uppercase">Vu et pris connaissance par les Parents/Tuteurs</p>
            <div class="h-20 border border-blue-200 rounded p-3 bg-white">
                @if($reportCard->parent_signed ?? false)
                    <p class="font-bold">Lu et approuvé</p>
                @else
                    <p class="text-gray-400 italic">En attente de signature</p>
                @endif
            </div>
        </div>

        <!-- Pied de page -->
        <div class="mt-8 text-center text-sm text-gray-600 border-t border-gray-300 pt-4">
            <p>{{ session('current_school')->name ?? 'Établissement' }} - Document officiel</p>
            <p>Généré le {{ now()->isoFormat('DD MMMM YYYY à HH:mm') }}</p>
        </div>

    </div>

</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: white;
        }
        #reportCard {
            box-shadow: none;
            border: 2px solid #1e40af;
            page-break-inside: avoid;
        }
    }
</style>

@endsection