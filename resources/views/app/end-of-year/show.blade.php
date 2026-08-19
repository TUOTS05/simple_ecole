@extends('layouts.app')

@section('content')
<div class="p-6 bg-white rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <a href="{{ route('app.end-of-year.index') }}" class="text-sm text-gray-500 hover:text-primary mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Retour aux classes
            </a>
            <h2 class="text-2xl font-bold text-gray-800">
                Classe : {{ $class->name }} ({{ ucfirst($class->cycle ?? 'Général') }})
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                Classe de destination suggérée : 
                @if($nextClass)
                    <span class="font-bold text-green-700">{{ $nextClass->name }}</span>
                @else
                    <span class="font-bold text-red-600">Aucune (Fin de cycle ou classe non créée)</span>
                @endif
            </p>
        </div>
        
        @if($nextClass)
            <form action="{{ route('app.end-of-year.migrate', $class->id) }}" method="POST" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir migrer TOUS les élèves ayant la décision \"Admis\" ou \"Saut de classe\" vers la classe {{ $nextClass->name }} ? Cette action est irréversible.');">
                @csrf
                <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-sm flex items-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Valider et Migrer les admis
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg border border-red-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Élève</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Moyenne T3</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Suggestion</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Décision & Destination</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
                       <tbody class="bg-white divide-y divide-gray-200">
                @forelse($students as $student)
                    @php
                        $reportCard = $student->reportCards->first();
                        $average = $reportCard ? $reportCard->average : 0;
                        $suggestion = $average >= 10 ? 'admis' : 'redouble';
                        $currentDecision = $reportCard ? $reportCard->end_of_year_decision : 'en_attente';
                        
                        // ✅ Détection du CM2 pour adapter l'affichage
                        $isCM2 = strtoupper($class->level) === 'CM2';
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ strtoupper($student->last_name ?? 'N/A') }} {{ $student->first_name ?? '' }}</div>
                            <div class="text-xs text-gray-500">Mat: {{ $student->matricule ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($reportCard && $reportCard->average > 0)
                                <span class="px-3 py-1 rounded-full text-sm font-bold {{ $average >= 10 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ number_format($average, 2) }}/20
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">Moyenne non calculée</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="text-sm capitalize font-medium {{ $suggestion === 'admis' ? 'text-green-600' : 'text-orange-600' }}">
                                {{ $suggestion === 'admis' ? '✅ Admis' : '🔁 Redouble' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('app.end-of-year.update-decision', $student->id) }}" method="POST" class="space-y-2">
                                @csrf
                                
                                {{-- ✅ Options adaptées pour le CM2 --}}
                                <select name="decision" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                                    @if($isCM2)
                                        <option value="admis" {{ $currentDecision === 'admis' ? 'selected' : '' }}>✅ Admis au concours d'entrée en 6ème</option>
                                        <option value="redouble" {{ $currentDecision === 'redouble' ? 'selected' : '' }}>🔁 Redouble le CM2</option>
                                    @else
                                        <option value="admis" {{ $currentDecision === 'admis' ? 'selected' : '' }}>✅ Admis</option>
                                        <option value="redouble" {{ $currentDecision === 'redouble' ? 'selected' : '' }}>🔁 Redouble</option>
                                        <option value="saut_classe" {{ $currentDecision === 'saut_classe' ? 'selected' : '' }}>⚡ Saut de classe</option>
                                    @endif
                                </select>

                                {{-- ✅ Masquer la classe de destination si c'est le CM2 --}}
                                @if(!$isCM2)
                                    <select name="next_school_class_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                                        @if($nextClass)
                                            <option value="{{ $nextClass->id }}" {{ ($reportCard && $reportCard->next_school_class_id == $nextClass->id) ? 'selected' : '' }}>
                                                {{ $nextClass->name }}
                                            </option>
                                        @else
                                            <option value="">Aucune classe supérieure</option>
                                        @endif
                                    </select>
                                @else
                                    <input type="hidden" name="next_school_class_id" value="">
                                    <div class="text-xs text-gray-500 italic mt-1">Fin de cycle primaire (pas de classe de destination)</div>
                                @endif

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition-colors shadow-sm">
                                    Enregistrer
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Aucun élève trouvé dans cette classe pour l'année en cours.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection