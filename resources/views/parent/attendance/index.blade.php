@extends('layouts.app')

@section('title', 'Suivi des présences')
@section('page_title', 'Suivi des présences')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- En-tête et Sélecteur d'enfant -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Présences de {{ $student->first_name }} {{ $student->last_name }}</h1>
            <p class="text-gray-600 text-sm mt-1">Classe : <span class="font-semibold text-primary">{{ $schoolClassName }}</span></p>
        </div>
        
        @if($siblings->count() > 1)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Changer d'enfant</label>
            <select onchange="window.location.href='{{ route('parent.attendance.index', ['studentId' => ':id']) }}'.replace(':id', this.value)" 
                    class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white">
                @foreach($siblings as $sibling)
                    <option value="{{ $sibling->id }}" {{ $student->id == $sibling->id ? 'selected' : '' }}>
                        {{ $sibling->first_name }} {{ $sibling->last_name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    <!-- Cartes de Statistiques -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Taux de présence</p>
            <p class="text-2xl font-bold text-primary">{{ $attendanceRate }}%</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Jours présents</p>
            <p class="text-2xl font-bold text-green-600">{{ $presentCount }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Jours absents</p>
            <p class="text-2xl font-bold text-red-600">{{ $absentCount }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500">Retards</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $lateCount }}</p>
        </div>
    </div>

    <!-- Filtres de Date -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <form method="GET" action="{{ route('parent.attendance.index', $student->id) }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white" />
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary bg-white" />
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition shadow">
                    🔍 Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau Détaillé -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Moment</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Observation</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $attendance)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm font-medium text-gray-800">
                        {{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('l d F Y') }}
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600 capitalize">
                        {{ str_replace('_', ' ', $attendance->period) }}
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($attendance->status === 'present')
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Présent</span>
                        @elseif($attendance->status === 'absent')
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">Absent</span>
                        @elseif($attendance->status === 'late')
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">Retard</span>
                        @elseif($attendance->status === 'excused')
                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">Excusé</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        {{ $attendance->notes ?: '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-500">
                        Aucune donnée de présence pour cette période.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection