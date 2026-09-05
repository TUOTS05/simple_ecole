@extends('layouts.app')

@section('title', 'Détails de l\'appel')
@section('page_title', 'Appel du ' . \Carbon\Carbon::parse($date)->format('d/m/Y'))

@section('content')
    
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('app.attendances.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour à l'historique
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Notes</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Pointé par</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $studentId => $records)
                        @php $attendance = $records->first(); @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50 {{ $attendance->isAbsent() ? 'bg-red-50' : '' }}">
                            <td class="py-3 px-4">
                                <div class="font-semibold">{{ $attendance->student->last_name }} {{ $attendance->student->first_name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $attendance->student->matricule }}</div>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                {{ $attendance->student->classes->first()->name ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center capitalize">
                                {{ $attendance->period === 'apres_midi' ? 'Après-midi' : 'Matin' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($attendance->isPresent())
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-accent text-white">✅ Présent</span>
                                @elseif($attendance->isAbsent())
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-danger text-white">❌ Absent</span>
                                @elseif($attendance->isLate())
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-secondary text-gray-800">⏱️ Retard</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary text-white">📝 Excusé</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600">{{ $attendance->notes ?? '—' }}</td>
                            <td class="py-3 px-4 text-sm text-gray-600">
                                {{ $attendance->markedBy->first_name ?? 'Système' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500">
                                Aucun appel enregistré pour cette date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
@endsection