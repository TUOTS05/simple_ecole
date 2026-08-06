@extends('layouts.app')

@section('title', 'Historique des présences')
@section('page_title', 'Bilan de mes classes')

@section('content')

@if(session('success'))
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6 flex items-center">
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    {{ session('success') }}
</div>
@endif

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Bilan des présences</h1>
        <p class="text-gray-600 mt-1">Consultez et exportez l'historique d'appel de vos classes.</p>
    </div>
    <div class="flex gap-2">
        <!-- Bouton Excel -->
        <a href="{{ route('teacher.attendance.export.excel', request()->query()) }}" 
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm">
            📗 Export Excel
        </a>
        <!-- Bouton PDF -->
        <a href="{{ route('teacher.attendance.export.pdf', request()->query()) }}" 
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm">
            📕 Export PDF
        </a>
    </div>
</div>

@if($assignments->isEmpty())
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
    <p class="text-yellow-800 font-semibold">⚠️ Aucune classe ne vous est assignée pour le moment.</p>
    <p class="text-yellow-700 text-sm mt-1">Veuillez contacter l'administration pour être assigné à une classe.</p>
</div>
@else

<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
    <form method="GET" action="{{ route('teacher.attendance.index') }}" class="grid gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ma Classe</label>
            <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                @foreach($assignments as $assignment)
                <option value="{{ $assignment->school_class_id }}" {{ $selectedClassId == $assignment->school_class_id ? 'selected' : '' }}>
                    {{ $assignment->schoolClass->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Moment de la journée</label>
            <select name="period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>Tous</option>
                @if($attendanceHasPeriod)
                <option value="matin" {{ $selectedPeriod === 'matin' ? 'selected' : '' }}>Matin</option>
                <option value="apres_midi" {{ $selectedPeriod === 'apres_midi' ? 'selected' : '' }}>Après-midi</option>
                @endif
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Regrouper par</label>
            <select name="group_by" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Jour</option>
                <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Semaine</option>
                <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Mois</option>
                <option value="year" {{ $groupBy === 'year' ? 'selected' : '' }}>Année</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white" />
        </div>
        <div class="md:col-span-5 flex justify-end gap-3 mt-2">
            <a href="{{ route('teacher.attendance.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">Réinitialiser</a>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition shadow">🔍 Filtrer</button>
        </div>
    </form>
</div>

<!-- Tableau de bilan global -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Enseignant</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Période</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                @if($attendanceHasPeriod)
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Moment</th>
                @endif
                <th class="text-center py-3 px-4 text-sm font-semibold text-green-600">✅ Présents</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-red-600">❌ Absents</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-yellow-600">⏱️ Retards</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-blue-600">📝 Excusés</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-800">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($summaries as $summary)
            <tr class="hover:bg-gray-50 transition">
                <td class="py-3 px-4 text-sm text-gray-700">{{ $summary->teacher_name ?? 'N/A' }}</td>
                <td class="py-3 px-4 font-medium text-gray-800">
                    @if($groupBy === 'week') Sem. du {{ \Carbon\Carbon::parse($summary->period_date)->startOfWeek()->format('d/m/Y') }}
                    @elseif($groupBy === 'month') {{ \Carbon\Carbon::parse($summary->period_date)->translatedFormat('F Y') }}
                    @elseif($groupBy === 'year') {{ \Carbon\Carbon::parse($summary->period_date)->format('Y') }}
                    @else {{ \Carbon\Carbon::parse($summary->period_date)->translatedFormat('l d F Y') }}
                    @endif
                </td>
                <td class="py-3 px-4 text-sm text-gray-700">{{ $summary->class_name }}</td>
                @if($attendanceHasPeriod)
                <td class="py-3 px-4 text-sm text-gray-600">
                    @if($summary->period === 'matin') 🌅 Matin
                    @elseif($summary->period === 'apres_midi' || $summary->period === 'apres-midi') 🌇 Après-midi
                    @else {{ ucfirst(str_replace('_', ' ', $summary->period)) }}
                    @endif
                </td>
                @endif
                <td class="py-3 px-4 text-center font-bold text-green-700">{{ $summary->present }}</td>
                <td class="py-3 px-4 text-center font-bold text-red-700">{{ $summary->absent }}</td>
                <td class="py-3 px-4 text-center font-bold text-yellow-700">{{ $summary->late }}</td>
                <td class="py-3 px-4 text-center font-bold text-blue-700">{{ $summary->excused }}</td>
                <td class="py-3 px-4 text-center font-bold text-gray-800">{{ $summary->total }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $attendanceHasPeriod ? 8 : 7 }}" class="py-12 text-center text-gray-500">
                    <div class="text-4xl mb-3">📭</div>
                    <p class="font-medium">Aucun appel enregistré pour cette période.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($summaries->hasPages())
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
        {{ $summaries->links() }}
    </div>
    @endif
</div>

<!-- ========================================== -->
<!-- NOUVEAU TABLEAU : Détail des heures d'absence par élève -->
<!-- ========================================== -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mt-8">
    <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <span class="mr-2">⏱️</span> Détail des heures d'absence par élève
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Règle de calcul : Matin = 4h (08h-12h) | Après-midi = 3h (14h-17h). 
                <span class="font-semibold text-primary">Période : du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matricule</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom et Prénom</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-red-600">Total Heures d'Absence</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Équivalent Jours</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($studentAbsenceHours as $student)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $student->matricule ?? 'N/A' }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ strtoupper($student->last_name) }} {{ ucfirst($student->first_name) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($student->total_hours > 0)
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full font-bold text-sm">
                                {{ $student->total_hours }} h
                            </span>
                        @else
                            <span class="text-gray-400 text-sm">0 h</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600">
                        @if($student->total_hours > 0)
                            {{ number_format($student->total_hours / 7, 1) }} jour(s)
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-500">
                        <div class="text-3xl mb-2">🎉</div>
                        <p class="font-medium">Aucune absence enregistrée pour cette classe sur la période sélectionnée.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endif
@endsection