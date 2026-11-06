@extends('layouts.app')

@section('title', 'Bulletins - ' . $student->first_name)
@section('page_title', 'Bulletins Scolaires')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête -->
    <div class="flex items-center mb-6">
        <a href="{{ route('parent.dashboard') }}" class="mr-4 text-gray-500 hover:text-primary transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Bulletins de {{ $student->first_name }} {{ $student->last_name }}</h1>
            <p class="text-sm text-gray-500">Historique des résultats scolaires</p>
        </div>
    </div>

    <!-- SÉLECTEUR D'ENFANT (visible seulement s'il y a plus d'un enfant) -->
    @if($siblings->count() > 1)
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <span class="font-semibold text-blue-800 text-sm">Vous avez plusieurs enfants. Sélectionnez celui dont vous voulez voir les bulletins :</span>
        <select onchange="window.location.href = '/parent/' + this.value + '/grades'" class="block w-full sm:w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary rounded-md shadow-sm bg-white">
            @foreach($siblings as $sibling)
                <option value="{{ $sibling->id }}" {{ $student->id == $sibling->id ? 'selected' : '' }}>
                    {{ $sibling->first_name }} {{ $sibling->last_name }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Calendrier de publication des bulletins de l'année en cours --}}
    @if($schedule)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Calendrier des bulletins</h2>
            <ul class="divide-y divide-gray-100">
                @foreach($schedule as $period)
                    @php
                        [$badge, $text] = match ($period['status']) {
                            'disponible' => ['bg-green-100 text-green-700', 'Disponible'],
                            'en préparation' => ['bg-yellow-100 text-yellow-800', 'En préparation'],
                            default => ['bg-gray-100 text-gray-600', 'Prévu'],
                        };
                    @endphp
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $period['label'] }}</p>
                            <p class="text-xs text-gray-500">Fin de période vers le {{ $period['until']->translatedFormat('d F Y') }}</p>
                        </div>
                        <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $badge }}">{{ $text }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-gray-500 mt-3">Chaque bulletin est mis en ligne par l'école après le conseil de classe de fin de période. Les dates sont indicatives.</p>
        </div>
    @endif

    <!-- Progression de la moyenne, toutes compositions et années confondues -->
    @if(count($progression['labels']) >= 2)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Progression de la moyenne</h2>
            <canvas id="progressionChart" height="90"></canvas>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            new Chart(document.getElementById('progressionChart'), {
                type: 'line',
                data: {
                    labels: @json($progression['labels']),
                    datasets: [{
                        label: 'Moyenne /20',
                        data: @json($progression['averages']),
                        borderColor: 'rgba(135, 206, 235, 1)',
                        backgroundColor: 'rgba(135, 206, 235, 0.2)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { min: 0, max: 20, ticks: { stepSize: 5 } } }
                }
            });
        </script>
        @endpush
    @endif

    <!-- Liste des bulletins -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-800">Bulletins disponibles</h3>
        </div>
        
        <div class="divide-y divide-gray-100">
            @forelse($reportCards as $card)
                <div class="p-6 hover:bg-gray-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <h4 class="font-bold text-gray-800 text-lg">{{ $card->schoolYear->name ?? 'Année inconnue' }}</h4>
                            <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full font-semibold">
                                {{ ucfirst($card->period) }} 
                                @if(strtolower($card->period) === 'mensuel' && $card->month)
                                    - {{ $card->month }}
                                @elseif(strtolower($card->period) === 'trimestriel' && $card->quarter)
                                    {{ $card->quarter }}ème Trimestre
                                @endif
                            </span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                {{ $card->schoolClass->name ?? 'Classe inconnue' }}
                            </span>
                        </div>
                        <div class="flex gap-4 text-sm text-gray-600 mt-2">
                            <span>Moyenne : <strong class="text-gray-800">{{ $card->average ?? '-' }}/20</strong></span>
                            <span>Classement : <strong class="text-gray-800">{{ $card->rank ?? '-' }}/{{ $card->total_students ?? '-' }}</strong></span>
                        </div>
                    </div>
                    
                    <a href="{{ route('parent.grades.pdf', [$student->id, $card->id]) }}" class="flex-shrink-0 flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold py-2.5 px-6 rounded-lg transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Télécharger le PDF
                    </a>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="font-semibold text-gray-600">Aucun bulletin n'a encore été publié pour {{ $student->first_name }}.</p>
                    <p class="text-sm mt-1">Les bulletins apparaissent ici dès leur publication par l'école{{ $schedule ? ' : consultez le calendrier ci-dessus.' : '.' }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection