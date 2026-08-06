@extends('layouts.app')

@section('title', 'Historique - ' . $class->name)
@section('page_title', 'Historique des présences')

@section('content')
    <div class="max-w-5xl mx-auto">
        <!-- En-tête -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $class->name }}</h2>
                <p class="text-gray-500">Historique des appels</p>
            </div>
            <a href="{{ route('teacher.attendance.create', ['class_id' => $class->id]) }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-bold shadow-md transition flex items-center">
                <i class="fas fa-clipboard-check mr-2"></i> Nouvel appel
            </a>
        </div>

        @if(isset($attendances) && $attendances->count() > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600 text-sm uppercase">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Date</th>
                                <th class="px-6 py-4 font-semibold">Période</th>
                                <th class="px-6 py-4 font-semibold text-center text-green-600">Présents</th>
                                <th class="px-6 py-4 font-semibold text-center text-red-600">Absents</th>
                                <th class="px-6 py-4 font-semibold text-center text-yellow-600">Retards</th>
                                <th class="px-6 py-4 font-semibold text-center text-blue-600">Excusés</th>
                                <th class="px-6 py-4 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($attendances as $record)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-800">
                                        {{ \Carbon\Carbon::parse($record['date'])->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-bold uppercase">
                                            {{ $record['period'] === 'matin' ? 'Matin' : 'Après-midi' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-green-600">{{ $record['present'] }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-red-600">{{ $record['absent'] }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-yellow-600">{{ $record['late'] }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-blue-600">{{ $record['excused'] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-600">{{ $record['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <i class="fas fa-clipboard text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg font-semibold">Aucun historique disponible</p>
                <p class="text-sm text-gray-400 mt-2">Commencez par faire l'appel de la classe.</p>
            </div>
        @endif
    </div>
@endsection