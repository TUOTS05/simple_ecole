@extends('layouts.app')

@section('title', 'Facture Consolidée')
@section('page_title', 'Facture Consolidée')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 mb-4">Regroupe sur un seul document, pour un élève et un mois donnés, la scolarité et tous les services extras dus ce mois-là.</p>
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[240px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève *</label>
                <select name="student_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($students as $s)
                    <option value="{{ $s->id }}" {{ $studentId == $s->id ? 'selected' : '' }}>{{ $s->last_name }} {{ $s->first_name }} ({{ $s->matricule }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                <input type="month" name="month" value="{{ $month }}" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Afficher</button>
            </div>
        </form>
    </div>

    @if($student)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">{{ $student->last_name }} {{ $student->first_name }} — {{ \Carbon\Carbon::parse($month.'-01')->translatedFormat('F Y') }}</h3>
            <a href="{{ route('extras.invoices.consolidated.pdf', ['student_id' => $studentId, 'month' => $month]) }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition text-sm">📄 Télécharger PDF</a>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Service</th>
                    <th class="text-left py-2 px-3 font-semibold text-gray-600">Échéance</th>
                    <th class="text-right py-2 px-3 font-semibold text-gray-600">Montant</th>
                    <th class="text-right py-2 px-3 font-semibold text-green-600">Payé</th>
                    <th class="text-right py-2 px-3 font-semibold text-red-600">Reste</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($lines as $line)
                <tr>
                    <td class="py-2 px-3 font-medium text-gray-800">{{ $line->service }}</td>
                    <td class="py-2 px-3 text-gray-500">{{ \Carbon\Carbon::parse($line->due_date)->format('d/m/Y') }}</td>
                    <td class="py-2 px-3 text-right">{{ number_format($line->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-2 px-3 text-right text-green-700">{{ number_format($line->paid, 0, ',', ' ') }} FCFA</td>
                    <td class="py-2 px-3 text-right text-red-700 font-semibold">{{ number_format($line->remaining, 0, ',', ' ') }} FCFA</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-500">Aucune échéance (scolarité ou extra) pour ce mois.</td></tr>
                @endforelse
            </tbody>
            @if($lines->isNotEmpty())
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td class="py-2 px-3" colspan="2">Total</td>
                    <td class="py-2 px-3 text-right">{{ number_format($totals->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-2 px-3 text-right text-green-700">{{ number_format($totals->paid, 0, ',', ' ') }} FCFA</td>
                    <td class="py-2 px-3 text-right text-red-700">{{ number_format($totals->remaining, 0, ',', ' ') }} FCFA</td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
    @endif
</div>
@endsection
