@extends('layouts.app')

@section('title', 'Impayés Extras')
@section('page_title', 'Impayés Extras')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année Scolaire</label>
                <select name="school_year_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" {{ $schoolYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Extra</label>
                <select name="extra_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Tous</option>
                    @foreach($extras as $extra)
                    <option value="{{ $extra->id }}" {{ $extraId == $extra->id ? 'selected' : '' }}>{{ $extra->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <a href="{{ route('extras.reports.unpaid.pdf', ['school_year_id' => $schoolYearId, 'extra_id' => $extraId]) }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition text-sm">📄 Exporter PDF</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 mb-1">Familles concernées</p>
            <p class="text-3xl font-bold text-gray-800">{{ $globalStats->families_count }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 mb-1">Montant total impayé</p>
            <p class="text-3xl font-bold text-red-600">{{ number_format($globalStats->total_unpaid, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Extra</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Total</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Payé</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-red-600">Reste</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($unpaid as $sub)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ $sub->student->last_name }} {{ $sub->student->first_name }}
                        <div class="text-xs text-gray-500 font-mono">{{ $sub->student->matricule }}</div>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $sub->extra->category->icon ?? '' }} {{ $sub->extra->name }}</td>
                    <td class="py-3 px-4 text-right text-gray-800">{{ number_format($sub->total_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right text-red-700 font-bold">{{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-12 text-center text-gray-500">Aucun impayé 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
