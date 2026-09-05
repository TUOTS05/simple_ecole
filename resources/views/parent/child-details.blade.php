@extends('layouts.app')

@section('title', $student->first_name . ' ' . $student->last_name)
@section('page_title', 'Fiche élève')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <a href="{{ route('parent.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary transition mb-4">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Retour au tableau de bord
    </a>

    <!-- En-tête enfant -->
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-xl shadow-md p-6 mb-6 text-white flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold flex-shrink-0">
            {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
        </div>
        <div class="flex-grow">
            <h1 class="text-xl font-bold">{{ $student->first_name }} {{ $student->last_name }}</h1>
            <p class="opacity-90 text-sm mt-1">
                {{ $schoolClass->name ?? 'Classe non assignée' }}
                @if($student->school) · {{ $student->school->name }} @endif
                @if($student->matricule) · Matricule {{ $student->matricule }} @endif
            </p>
        </div>

        @if($schoolYears->count() > 1)
            <form method="GET" class="flex-shrink-0">
                <label for="year" class="sr-only">Année scolaire</label>
                <select name="year" id="year" onchange="this.form.submit()"
                    class="bg-white/10 border border-white/30 text-white text-sm rounded-lg px-4 py-2 focus:ring-2 focus:ring-white/50 focus:outline-none [&>option]:text-gray-800">
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" @selected($currentYear && $currentYear->id === $year->id)>
                            {{ $year->name }}{{ $year->is_active ? ' (en cours)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if(!$currentYear)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 px-6 py-4 rounded-lg mb-6">
            Aucune année scolaire active n'a été trouvée pour l'école de {{ $student->first_name }}.
        </div>
    @elseif(!$enrollment)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 px-6 py-4 rounded-lg mb-6">
            {{ $student->first_name }} n'a pas d'inscription enregistrée pour l'année {{ $currentYear->name }}.
        </div>
    @else
        <!-- Statistiques rapides -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 rounded-xl border border-blue-100">
                <p class="text-xs text-blue-600 font-bold uppercase tracking-wider mb-1">Moyenne</p>
                <p class="text-2xl font-bold text-blue-800">
                    {{ $average ?? '--' }}<span class="text-sm font-normal text-blue-600">/20</span>
                </p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-xl border border-green-100">
                <p class="text-xs text-green-600 font-bold uppercase tracking-wider mb-1">Présence (30j)</p>
                <p class="text-2xl font-bold text-green-800">
                    {{ $attendanceRate }}<span class="text-sm font-normal text-green-600">%</span>
                </p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-xl border border-yellow-100">
                <p class="text-xs text-yellow-600 font-bold uppercase tracking-wider mb-1">Scolarité payée</p>
                <p class="text-2xl font-bold text-yellow-800">
                    {{ $paymentRate }}<span class="text-sm font-normal text-yellow-600">%</span>
                </p>
            </div>
        </div>

        <!-- Situation financière -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Situation financière — {{ $currentYear->name }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Frais de scolarité</p>
                    <p class="font-semibold text-gray-800">{{ number_format($enrollment->tuition_fee_total, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Déjà payé</p>
                    <p class="font-semibold text-green-700">{{ number_format($enrollment->tuition_fee_paid, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Reste à payer</p>
                    <p class="font-semibold {{ $enrollment->tuition_fee_remaining > 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ number_format(max(0, $enrollment->tuition_fee_remaining), 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Frais d'inscription</p>
                    <p class="font-semibold {{ $enrollment->registration_fee_paid ? 'text-green-700' : 'text-red-600' }}">
                        {{ $enrollment->registration_fee_paid ? 'Payés' : 'Non payés' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Statut d'inscription</p>
                    <p class="font-semibold text-gray-800">
                        @switch($enrollment->status)
                            @case('enrolled') Inscrit @break
                            @case('reserved') Réservé @break
                            @case('withdrawn') Retiré @break
                            @default {{ $enrollment->status }}
                        @endswitch
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Présence sur 30 jours</p>
                    <p class="font-semibold text-gray-800">{{ $presentDays }} / {{ $totalDays }} jours</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Progression de la moyenne, toutes compositions et années confondues -->
    @if(count($progression['labels']) >= 2)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Progression de la moyenne — toutes années</h2>
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

    <!-- Menu d'actions -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <a href="{{ route('parent.grades.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
            <div class="text-3xl mb-2">📊</div>
            <h3 class="font-semibold text-gray-700 text-sm">Notes</h3>
        </a>

        <a href="{{ route('parent.attendance.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
            <div class="text-3xl mb-2">📅</div>
            <h3 class="font-semibold text-gray-700 text-sm">Présences</h3>
        </a>

        <a href="{{ route('parent.payments.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
            <div class="text-3xl mb-2">💰</div>
            <h3 class="font-semibold text-gray-700 text-sm">Paiements</h3>
        </a>

        <a href="{{ route('parent.extras.index', $student->id) }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
            <div class="text-3xl mb-2">🧩</div>
            <h3 class="font-semibold text-gray-700 text-sm">Mes extras</h3>
        </a>

        <a href="{{ route('parent.messages.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition">
            <div class="text-3xl mb-2">📨</div>
            <h3 class="font-semibold text-gray-700 text-sm">Messages</h3>
        </a>
    </div>

</div>
@endsection
