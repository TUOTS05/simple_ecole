@extends('layouts.app')

@section('title', 'Historique des présences')
@section('page_title', 'Historique des présences')

@section('content')

@if(session('success'))
<div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
    {{ session('success') }}
</div>
@endif

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Bilan des présences</h1>
        <p class="text-gray-600 mt-1">Filtrer par classe, période et intervalle de temps.</p>
    </div>
    <a href="{{ route('app.attendances.create') }}"
        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
        + Faire l'appel
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('app.attendances.index') }}" class="grid gap-4 md:grid-cols-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
            <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">Toutes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->cycle }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
            <select name="period" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>Toutes</option>
                @if($attendanceHasPeriod)
                    <option value="matin" {{ $selectedPeriod === 'matin' ? 'selected' : '' }}>Matin</option>
                    <option value="apres_midi" {{ $selectedPeriod === 'apres_midi' ? 'selected' : '' }}>Après-midi</option>
                @endif
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Vue</label>
            <select name="group_by" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Jour</option>
                <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Semaine</option>
                <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Mois</option>
                <option value="year" {{ $groupBy === 'year' ? 'selected' : '' }}>Année</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary" />
        </div>

        <div class="md:col-span-5 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                Filtrer
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Période</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                @if($attendanceHasPeriod)
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Plage</th>
                @endif
                <th class="text-center py-3 px-4 text-sm font-semibold text-accent">✅ Présents</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-danger">❌ Absents</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-secondary">⏱️ Retards</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-primary">📝 Excusés</th>
                <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaries as $summary)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-semibold">
                    @if($groupBy === 'week')
                        Semaine du {{ \Carbon\Carbon::parse($summary->period_date)->startOfWeek()->format('d/m/Y') }}
                    @elseif($groupBy === 'month')
                        {{ \Carbon\Carbon::parse($summary->period_date)->translatedFormat('F Y') }}
                    @elseif($groupBy === 'year')
                        {{ \Carbon\Carbon::parse($summary->period_date)->format('Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($summary->period_date)->format('d/m/Y') }}
                    @endif
                </td>
                <td class="py-3 px-4 text-sm">{{ $summary->class_name }}</td>
                @if($attendanceHasPeriod)
                    <td class="py-3 px-4 text-sm">{{ $summary->period === 'apres_midi' ? 'Après-midi' : 'Matin' }}</td>
                @endif
                <td class="py-3 px-4 text-center font-bold text-accent">{{ $summary->present }}</td>
                <td class="py-3 px-4 text-center font-bold text-danger">{{ $summary->absent }}</td>
                <td class="py-3 px-4 text-center font-bold text-yellow-600">{{ $summary->late }}</td>
                <td class="py-3 px-4 text-center font-bold text-blue-600">{{ $summary->excused }}</td>
                <td class="py-3 px-4 text-center font-bold text-gray-800">{{ $summary->total }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $attendanceHasPeriod ? 8 : 7 }}" class="py-8 text-center text-gray-500">
                    Aucun bilan de présence trouvé pour ces filtres.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($summaries->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $summaries->links() }}
    </div>
    @endif
</div>

@endsection