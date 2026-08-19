<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.3;
            padding: 15mm;
        }

        /* ✅ CADRE GLOBAL AUTOUR DU BULLETIN */
        .bulletin-container {
            border: 2px solid #000; /* Changez en #1e40af pour un cadre bleu */
            padding: 20px;
            min-height: 90mm;
        }

        /* En-tête : Logo à gauche, Infos école à droite */
        .header-table {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .logo-cell {
            width: 30%;
            text-align: center;
        }
        .info-cell {
            width: 70%;
            text-align: center;
            padding-left: 15px;
        }

        /* Titre du bulletin */
        .title-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .bulletin-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .period {
            font-size: 11px;
        }

        /* Informations élève */
        .student-info {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #000;
        }
        .student-info .row {
            display: table-row;
        }
        .student-info .cell {
            display: table-cell;
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
        }
        .student-info .cell strong {
            font-weight: bold;
        }

        /* Tableau des notes */
        table.grades {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.grades th,
        table.grades td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9px;
        }
        table.grades th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        table.grades td {
            text-align: left;
        }
        table.grades td.center {
            text-align: center;
        }

        /* Résumé */
        .summary {
            margin-bottom: 10px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .summary-cell {
            display: table-cell;
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9px;
            width: 33.33%; /* ✅ 3 colonnes égales sur la même ligne */
        }
        .summary-cell strong {
            font-weight: bold;
        }

        /* Signatures */
        .signatures {
            margin-top: 15px;
        }
        .signature-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 5px;
        }
        .signature-cell.full-width {
            width: 100%;
        }
        .signature-box {
            border: 1px solid #000;
            min-height: 45px;
            padding: 5px;
            font-size: 9px;
        }
        .signature-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 9px;
        }

        /* Pied de page */
        .footer {
            margin-top: 15px;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- ✅ DÉBUT DU CADRE GLOBAL -->
    <div class="bulletin-container">

        <!-- En-tête : Logo à gauche, Infos école à droite -->
        <div class="header-table">
            <div class="header-cell logo-cell">
                @if($school && $school->logo_path && file_exists(public_path('storage/' . $school->logo_path)))
                    <img src="{{ public_path('storage/' . $school->logo_path) }}" alt="Logo" style="max-width: 80px; max-height: 80px;">
                @else
                    <div style="font-size: 10px; color: #666; border: 1px dashed #999; padding: 10px; display: inline-block;">[ LOGO ]</div>
                @endif
                <div style="font-size: 10px; margin-top: 8px;">Année Scolaire: <strong>{{ $schoolYear->name ?? '2025-2026' }}</strong></div>
            </div>
            <div class="header-cell info-cell">
                <div style="font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">{{ $school->name ?? 'NOM DE L\'ÉTABLISSEMENT SCOLAIRE' }}</div>
                <div style="font-size: 9px; color: #333;">
                    Adresse: {{ $school->address ?? 'BP 1234 Abidjan- Côte d\'Ivoire' }}<br>
                    Téléphone: {{ $school->phone ?? '+225 01 53 91 91 75/ 07 89 57 32 72' }}<br>
                    Email: {{ $school->email ?? 'contact@ecole-excellence.ci' }}
                </div>
            </div>
        </div>

        <!-- Titre du bulletin -->
        <div class="title-section">
            @php
                $periodLower = strtolower($reportCard->period ?? 'trimestriel');
            @endphp

            @if($periodLower === 'mensuel')
                <div class="bulletin-title">BULLETIN MENSUEL</div>
                <div class="period">Mois de: <strong>{{ $reportCard->month ?? '___________________________' }}</strong></div>
            @else
                <div class="bulletin-title">BULLETIN TRIMESTRIEL</div>
                <div class="period"><strong>{{ $reportCard->quarter ?? '1' }}ème Trimestre</strong> - {{ $schoolYear->name ?? '2025-2026' }}</div>
            @endif
        </div>

        <!-- Informations élève -->
        <div class="student-info">
            <div class="row">
                <div class="cell"><strong>MATRICULE:</strong> {{ $student->matricule ?? 'N/A' }}</div>
                <div class="cell"><strong>ÉLÈVE:</strong> {{ strtoupper($student->last_name) }} {{ $student->first_name }}</div>
                <div class="cell"><strong>CLASSE:</strong> {{ $schoolClass->name ?? 'Non assignée' }}</div>
            </div>
        </div>

        <!-- Tableau des notes -->
        <table class="grades">
            <thead>
                <tr>
                    <th style="width: 40%;">MATIÈRE/ DISCIPLINE</th>
                    <th style="width: 15%;">NOTE/ 20</th>
                    <th style="width: 10%;">COEFF</th>
                    <th style="width: 35%;">APPRÉCIATION/ OBSERVATION</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalWeightedScore = 0;
                $totalCoefficients = 0;
                @endphp

                @foreach($allSubjects as $subject)
                    @php
                    $grade = $reportCard->grades->firstWhere('subject_id', $subject->id);
                    $score = $grade ? $grade->score : null;
                    $maxScore = $grade ? ($grade->max_score ?? 20) : ($subject->max_score ?? 20);
                    $coefficient = $grade ? ($grade->coefficient_used ?? $subject->coefficient ?? 1) : ($subject->coefficient ?? 1);

                    $displayAppreciation = '--';
                    $hasGrade = false;

                    if ($score !== null) {
                        $scoreOutOf20 = ($score / $maxScore) * 20;
                        $totalWeightedScore += ($scoreOutOf20 * $coefficient);
                        $totalCoefficients += $coefficient;
                        $hasGrade = true;

                        // Calcul automatique de la mention
                        if ($scoreOutOf20 >= 18) $appreciation = 'Excellent';
                        elseif ($scoreOutOf20 >= 16) $appreciation = 'Très Bien';
                        elseif ($scoreOutOf20 >= 14) $appreciation = 'Bien';
                        elseif ($scoreOutOf20 >= 12) $appreciation = 'Assez Bien';
                        elseif ($scoreOutOf20 >= 10) $appreciation = 'Passable';
                        else $appreciation = 'Insuffisant';

                        // Utilise l'observation saisie manuellement, sinon la mention calculée
                        $displayAppreciation = $grade->remarks ?: $appreciation;
                    }
                    @endphp

                    <tr>
                        <td>{{ $subject->name }}</td>
                        <td class="center">
                            @if($hasGrade)
                                {{ number_format($scoreOutOf20, 2) }}
                            @else
                                --
                            @endif
                        </td>
                        <td class="center">{{ $coefficient }}</td>
                        <td class="italic">{{ $displayAppreciation }}</td>
                    </tr>
                @endforeach

                <!-- Ligne Moyenne Générale -->
                <tr>
                    <td style="text-align: right; font-weight: bold; text-transform: uppercase;">MOYENNE GÉNÉRALE</td>
                    <td class="center" style="font-weight: bold; font-size: 11px;">
                        @if($totalCoefficients > 0)
                            {{ number_format($totalWeightedScore / $totalCoefficients, 2) }}
                        @else
                            --
                        @endif
                    </td>
                    <td class="center"></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Résumé (3 éléments sur la MÊME LIGNE) -->
        <div class="summary">
            <div class="summary-row">
                <div class="summary-cell">
                    <strong>CLASSEMENT (RANG) :</strong> {{ $reportCard->rank ?? '--' }}
                </div>
                <div class="summary-cell">
                    <strong>EFFECTIF DE LA CLASSE :</strong> {{ $reportCard->total_students ?? '--' }} Élèves
                </div>
                <div class="summary-cell">
                    <strong>MENTION DU CONSEIL :</strong>
                    @php
                    $avg = $totalCoefficients > 0 ? ($totalWeightedScore / $totalCoefficients) : 0;
                    if ($avg >= 16) echo 'Très Bien';
                    elseif ($avg >= 14) echo 'Bien';
                    elseif ($avg >= 12) echo 'Assez Bien';
                    elseif ($avg >= 10) echo 'Passable';
                    else echo 'Insuffisant';
                    @endphp
                </div>
            </div>
        </div>

        <!-- Signatures (MODIFIÉ POUR INCLURE L'ENSEIGNANT) -->
                <!-- Signatures & Décision de fin d'année -->
        <div class="signatures">
            @php
                // Vérifie si c'est le 3ème trimestre (insensible à la casse)
                $isThirdTrimester = (strtolower($reportCard->period ?? '') === 'trimestriel' && $reportCard->quarter == 3);
            @endphp

            @if($isThirdTrimester)
                <!-- ✅ BLOC DÉCISION DU CONSEIL DE CLASSE (Uniquement au 3ème trimestre) -->
                <div style="border: 2px solid #000; padding: 12px; margin-bottom: 15px; background-color: #f9fafb; text-align: center;">
                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 10px; font-size: 12px; letter-spacing: 1px;">
                        DÉCISION DU CONSEIL DE CLASSE
                    </div>
                    
                    @if(isset($reportCard->end_of_year_decision) && $reportCard->end_of_year_decision !== 'en_attente')
                        @if($reportCard->end_of_year_decision === 'admis')
                            <div style="font-size: 16px; font-weight: bold; color: #166534; margin-bottom: 5px;">
                                ✅ ADMIS(E) EN {{ $reportCard->nextSchoolClass ? strtoupper($reportCard->nextSchoolClass->name) : 'CLASSE SUPÉRIEURE' }}
                            </div>
                        @elseif($reportCard->end_of_year_decision === 'redouble')
                            <div style="font-size: 16px; font-weight: bold; color: #9a3412; margin-bottom: 5px;">
                                🔁 REDOUBLEMENT
                            </div>
                        @elseif($reportCard->end_of_year_decision === 'saut_classe')
                            <div style="font-size: 16px; font-weight: bold; color: #1e40af; margin-bottom: 5px;">
                                ⚡ SAUT DE CLASSE VERS {{ $reportCard->nextSchoolClass ? strtoupper($reportCard->nextSchoolClass->name) : 'CLASSE SUPÉRIEURE' }}
                            </div>
                        @endif

                        @if(!empty($reportCard->director_comment))
                            <div style="margin-top: 10px; font-size: 10px; font-style: italic; color: #333; border-top: 1px dashed #ccc; padding-top: 8px;">
                                "{{ $reportCard->director_comment }}"
                            </div>
                        @endif
                    @else
                        <div style="font-size: 11px; color: #666; font-style: italic;">
                            En attente de la décision finale du conseil de classe.
                        </div>
                    @endif
                </div>
            @else
                <!-- ✅ COMPORTEMENT NORMAL POUR 1er et 2ème TRIMESTRE -->
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 5px; font-size: 10px;">
                        APPRÉCIATION DU CONSEIL DE CLASSE
                    </div>
                    <div style="border: 1px solid #000; padding: 8px; min-height: 40px; font-size: 10px;">
                        {{ $reportCard->director_comment ?: 'Aucune appréciation globale renseignée.' }}
                    </div>
                </div>
            @endif

            <!-- Ligne des signatures (Toujours présente) -->
            <div class="signature-row">
                <div class="signature-cell">
                    <div class="signature-title">APPRÉCIATION GÉNÉRALE & OBSERVATIONS</div>
                    <div class="signature-box">
                        {{ $reportCard->teacher_comment ?? '' }}
                    </div>
                </div>
                <div class="signature-cell">
                    <div class="signature-title">LA DIRECTION/ CACHET & SIGNATURE</div>
                    <div class="signature-box"></div>
                </div>
            </div>
        </div><br>

        <!-- Pied de page -->
        <div class="footer">
            Fait à: {{ $school->city ?? 'Abidjan' }}
            Le: {{ now()->format('d/m/Y') }}
        </div>

    </div>
    <!-- ✅ FIN DU CADRE GLOBAL -->

</body>
</html>