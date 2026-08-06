<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin - {{ $student->first_name }} {{ $student->last_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Styles spécifiques pour l'impression */
        @media print {
            .no-print { display: none !important; }
            body { background: white; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .shadow-lg { box-shadow: none !important; }
            .border { border: 1px solid #ddd !important; }
            /* Empêche les lignes du tableau d'être coupées entre deux pages */
            tr { page-break-inside: avoid; break-inside: avoid; }
        }
        @page { margin: 1.5cm; size: A4 portrait; }
        
        /* Police plus lisible pour l'impression */
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    
    <!-- Bouton retour et impression (caché à l'impression) -->
    <div class="max-w-3xl mx-auto mb-4 flex justify-between items-center no-print">
        <a href="{{ route('parent.grades.index', $student->id) }}" class="flex items-center text-blue-600 hover:text-blue-800 font-medium bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200 hover:bg-gray-50 transition">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Retour à la liste
        </a>
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimer / PDF
        </button>
    </div>

    <!-- Contenu du Bulletin (Format A4) -->
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg border border-gray-200">
        
        <!-- En-tête de l'école -->
        <div class="text-center border-b-2 border-blue-600 pb-6 mb-6">
            <!-- Emplacement pour le logo (si vous en avez un, remplacez le texte par <img>) -->
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3 text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 uppercase tracking-wide">{{ $student->school->name ?? 'ÉTABLISSEMENT SCOLAIRE' }}</h1>
            <p class="text-gray-600 mt-2 font-medium">Année Scolaire : {{ $reportCard->schoolYear->name ?? 'N/A' }}</p>
            <h2 class="text-xl font-bold text-blue-600 mt-4 tracking-wider">BULLETIN DE NOTES</h2>
            <p class="text-sm text-gray-500 mt-1">Période : {{ $reportCard->period ?? 'Trimestre' }}</p>
        </div>

        <!-- Informations de l'élève -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 mb-6 bg-gray-50 p-5 rounded-lg border border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Nom et Prénom</p>
                <p class="text-base font-bold text-gray-800 mt-1">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Classe</p>
                <p class="text-base font-bold text-gray-800 mt-1">{{ $reportCard->schoolClass->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Matricule</p>
                <p class="text-base font-mono font-bold text-gray-800 mt-1">{{ $student->matricule ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Date d'édition</p>
                <p class="text-base font-bold text-gray-800 mt-1">{{ \Carbon\Carbon::parse($reportCard->created_at ?? now())->format('d/m/Y') }}</p>
            </div>
        </div>

        <!-- Tableau des notes -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="border border-blue-700 px-4 py-3 text-left font-semibold w-1/2">Matière</th>
                        <th class="border border-blue-700 px-4 py-3 text-center font-semibold w-24">Note /20</th>
                        <th class="border border-blue-700 px-4 py-3 text-left font-semibold">Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportCard->grades as $grade)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-3 font-medium text-gray-800">
                                {{ $grade->subject->name ?? 'Matière inconnue' }}
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-center font-bold text-blue-600 text-base">
                                {{ number_format($grade->score ?? 0, 1, ',', ' ') }}
                            </td>
                            <td class="border border-gray-300 px-4 py-3 text-gray-600 italic">
                                {{ $grade->comment ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="border border-gray-300 px-4 py-8 text-center text-gray-500 bg-gray-50">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Aucune note enregistrée pour cette période.
                            </td>
                        </tr>
                    @endforelse
                    
                    <!-- Ligne de résumé global (à adapter si vous avez ces champs dans votre modèle) -->
                    @if($reportCard->grades->count() > 0)
                        <tr class="bg-blue-50 font-bold">
                            <td class="border border-blue-200 px-4 py-3 text-blue-800 uppercase tracking-wide text-xs">Moyenne Générale</td>
                            <td class="border border-blue-200 px-4 py-3 text-center text-blue-800 text-lg">
                                {{ number_format($reportCard->average ?? ($reportCard->grades->avg('score') ?? 0), 2, ',', ' ') }}
                            </td>
                            <td class="border border-blue-200 px-4 py-3 text-blue-800 italic text-xs">
                                {{ $reportCard->global_comment ?? 'Appréciation générale du conseil de classe' }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pied de page et signatures -->
        <div class="mt-10 pt-6 border-t-2 border-gray-200">
            <div class="grid grid-cols-2 gap-8 text-center">
                <div class="h-24 flex flex-col justify-end">
                    <p class="text-sm font-bold text-gray-800 mb-8">Le Directeur / La Directrice</p>
                    <p class="text-xs text-gray-500">(Signature et Cachet)</p>
                </div>
                <div class="h-24 flex flex-col justify-end">
                    <p class="text-sm font-bold text-gray-800 mb-8">Le Parent / Tuteur</p>
                    <p class="text-xs text-gray-500">(Lu et approuvé)</p>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-gray-400 border-t border-gray-100 pt-4">
            <p>Ce bulletin est généré automatiquement par le système. Toute rature ou altération le rend nul.</p>
        </div>

    </div>
</body>
</html>