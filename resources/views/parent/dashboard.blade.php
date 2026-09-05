@extends('layouts.app')

@section('title', 'Tableau de bord Parent')
@section('page_title', 'Tableau de bord')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête accueillant -->
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-xl shadow-md p-6 mb-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold mb-2">Bonjour, {{ auth()->user()->first_name }} 👋</h2>
            <p class="opacity-90 max-w-2xl">
                Retrouvez ici le suivi scolaire, les présences et la situation administrative de vos enfants pour l'année en cours.
            </p>
        </div>

        @if($schoolYears->count() > 1)
            <form method="GET" class="flex-shrink-0">
                <label for="year" class="sr-only">Année scolaire</label>
                <select name="year" id="year" onchange="this.form.submit()"
                    class="bg-white/10 border border-white/30 text-white text-sm rounded-lg px-4 py-2 focus:ring-2 focus:ring-white/50 focus:outline-none [&>option]:text-gray-800">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" @selected(request('year') == $year->id)>
                            {{ $year->name }}{{ $year->is_active ? ' (en cours)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    <!-- Statistiques globales -->
    @if($globalStats['totalChildren'] > 0)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Enfants</p>
                <p class="text-2xl font-bold text-gray-800">{{ $globalStats['totalChildren'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Présence moyenne</p>
                <p class="text-2xl font-bold text-gray-800">
                    {{ $globalStats['averageAttendance'] !== null ? round($globalStats['averageAttendance'], 1) : '--' }}<span class="text-sm font-normal text-gray-500">%</span>
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Frais de scolarité totaux</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($globalStats['totalFees'], 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span></p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Reste à payer</p>
                <p class="text-2xl font-bold {{ ($globalStats['totalFees'] - $globalStats['totalPaid']) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format(max(0, $globalStats['totalFees'] - $globalStats['totalPaid']), 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span>
                </p>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush

    <!-- Grille des enfants -->
    @if(count($childrenBySchool) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($childrenBySchool as $schoolId => $childrenData)
                @foreach($childrenData as $data)
                    @php
                        $student = $data['student'];
                        $schoolClass = $data['schoolClass'];
                        $average = $data['average'];
                        $attendance = $data['attendanceRate'];
                        $payment = $data['paymentRate'];
                    @endphp
                    
                    <!-- Carte Enfant -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                        
                        <!-- En-tête de la carte -->
                        <a href="{{ route('parent.child.details', $student->id) }}" class="p-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-4 hover:bg-gray-100/70 transition">
                            <div class="w-14 h-14 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xl font-bold shadow-sm flex-shrink-0">
                                {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 leading-tight">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                </h3>
                                @if($schoolClass)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary bg-primary/10 px-2.5 py-1 rounded-full mt-1.5">
                                        🏫 {{ $schoolClass->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full mt-1.5">
                                        Classe non assignée
                                    </span>
                                @endif
                            </div>
                        </a>

                        <!-- Statistiques rapides (Mini-cartes) -->
                        <div class="p-5 grid grid-cols-3 gap-3 flex-grow">
                            <!-- Moyenne -->
                            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <p class="text-[10px] text-blue-600 font-bold uppercase tracking-wider mb-1">Moyenne</p>
                                <p class="text-xl font-bold text-blue-800">
                                    {{ $average ? $average : '--' }}<span class="text-sm font-normal text-blue-600">/20</span>
                                </p>
                            </div>
                            <!-- Présence -->
                            <div class="text-center p-3 bg-green-50 rounded-lg border border-green-100">
                                <p class="text-[10px] text-green-600 font-bold uppercase tracking-wider mb-1">Présence</p>
                                <p class="text-xl font-bold text-green-800">
                                    {{ $attendance }}<span class="text-sm font-normal text-green-600">%</span>
                                </p>
                            </div>
                            <!-- Scolarité -->
                            <div class="text-center p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                                <p class="text-[10px] text-yellow-600 font-bold uppercase tracking-wider mb-1">Scolarité</p>
                                <p class="text-xl font-bold text-yellow-800">
                                    {{ $payment }}<span class="text-sm font-normal text-yellow-600">%</span>
                                </p>
                            </div>
                        </div>

                        <!-- Progression de la moyenne (compositions récentes, toutes années) -->
                        @if(count($data['progression']['labels']) >= 2)
                            <div class="px-5 pb-3">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1.5">Progression de la moyenne</p>
                                <canvas id="progressionChart{{ $student->id }}" height="70"></canvas>
                            </div>
                            @push('scripts')
                            <script>
                                new Chart(document.getElementById('progressionChart{{ $student->id }}'), {
                                    type: 'line',
                                    data: {
                                        labels: @json($data['progression']['labels']),
                                        datasets: [{
                                            label: 'Moyenne /20',
                                            data: @json($data['progression']['averages']),
                                            borderColor: 'rgba(135, 206, 235, 1)',
                                            backgroundColor: 'rgba(135, 206, 235, 0.2)',
                                            tension: 0.3,
                                            fill: true,
                                            pointRadius: 3
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            y: { min: 0, max: 20, ticks: { stepSize: 5 } },
                                            x: { ticks: { font: { size: 9 } } }
                                        }
                                    }
                                });
                            </script>
                            @endpush
                        @endif

                        <!-- Boutons d'action rapide -->
                        <div class="p-5 pt-0 grid grid-cols-2 gap-3 mt-auto">
                            <a href="{{ route('parent.grades.index', $student->id) }}" class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold py-2.5 px-3 rounded-lg transition shadow-sm text-sm">
                                📄 Bulletins
                            </a>
                            <a href="{{ route('parent.attendance.index', $student->id) }}" class="flex items-center justify-center gap-2 bg-green-50 hover:bg-green-100 text-green-700 font-semibold py-2.5 px-3 rounded-lg transition border border-green-200 text-sm">
                                ✅ Présences
                            </a>
                            <a href="{{ route('parent.payments.index', $student->id) }}" class="flex items-center justify-center gap-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 font-semibold py-2.5 px-3 rounded-lg transition border border-yellow-200 text-sm">
                                💳 Paiements
                            </a>
                            <a href="{{ route('parent.messages.index') }}" class="flex items-center justify-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold py-2.5 px-3 rounded-lg transition border border-gray-200 text-sm">
                                ✉️ Messages
                            </a>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <!-- État vide si aucun enfant n'est inscrit -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Aucun enfant inscrit pour le moment</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-6">
                Aucun de vos enfants n'est actuellement inscrit ou associé à votre compte pour l'année scolaire en cours. 
                Veuillez contacter l'administration de l'établissement si vous pensez qu'il s'agit d'une erreur.
            </p>
            <a href="{{ route('parent.messages.create') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-6 rounded-lg transition shadow-sm">
                ✉️ Contacter l'administration
            </a>
        </div>
    @endif

</div>
@endsection