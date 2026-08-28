@extends('layouts.app')

@section('title', 'Tableau de bord Extras')
@section('page_title', 'Tableau de bord — Extras')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Extras actifs</p>
            <p class="text-2xl font-bold text-gray-800">{{ $kpis->active_extras_count }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Élèves abonnés</p>
            <p class="text-2xl font-bold text-gray-800">{{ $kpis->subscribed_students_count }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Impayés</p>
            <p class="text-2xl font-bold text-red-600">{{ $kpis->unpaid_count }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">CA facturé</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($kpis->total_invoiced, 0, ',', ' ') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Encaissé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($kpis->total_collected, 0, ',', ' ') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Reste à encaisser</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($kpis->total_unpaid, 0, ',', ' ') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 mb-1 uppercase font-semibold">Taux de paiement</p>
            <p class="text-2xl font-bold text-primary">{{ $kpis->payment_rate }}%</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">📈 CA encaissé par mois (12 derniers mois)</h3>
        <canvas id="monthlyRevenueChart" height="90"></canvas>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💰 Chiffre d'affaires par extra</h3>
        @if($byExtra->isEmpty())
        <p class="text-gray-500 text-center py-8">Aucune donnée pour cette année scolaire.</p>
        @else
        <canvas id="byExtraChart" height="100"></canvas>

        <div class="overflow-x-auto mt-6">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Extra</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Élèves</th>
                        <th class="text-right py-2 px-3 font-semibold text-gray-600">Facturé</th>
                        <th class="text-right py-2 px-3 font-semibold text-green-600">Encaissé</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($byExtra as $row)
                    <tr>
                        <td class="py-2 px-3 font-medium text-gray-800">{{ $row->name }}</td>
                        <td class="py-2 px-3 text-center text-gray-600">{{ $row->students_count }}</td>
                        <td class="py-2 px-3 text-right text-gray-800">{{ number_format($row->total_invoiced, 0, ',', ' ') }} FCFA</td>
                        <td class="py-2 px-3 text-right text-green-700 font-semibold">{{ number_format($row->total_collected, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('label')) !!},
            datasets: [{
                label: 'CA encaissé (FCFA)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                fill: true,
                tension: 0.3,
            }],
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } },
    });

    @if($byExtra->isNotEmpty())
    new Chart(document.getElementById('byExtraChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($byExtra->pluck('name')) !!},
            datasets: [{
                label: 'Encaissé (FCFA)',
                data: {!! json_encode($byExtra->pluck('total_collected')) !!},
                backgroundColor: '#2563eb',
            }, {
                label: 'Facturé (FCFA)',
                data: {!! json_encode($byExtra->pluck('total_invoiced')) !!},
                backgroundColor: '#93c5fd',
            }],
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } },
    });
    @endif
</script>
@endpush
@endsection
