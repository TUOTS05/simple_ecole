@extends('layouts.app')

@section('title', 'Présences Extras')
@section('page_title', 'Présences / Consommations')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-end gap-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Extra *</label>
                <select name="extra_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($extras as $extra)
                    <option value="{{ $extra->id }}" {{ $extraId == $extra->id ? 'selected' : '' }}>{{ $extra->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </form>
        <a href="{{ route('extras.attendances.scan') }}" class="bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-lg font-semibold text-sm transition">📷 Pointage rapide (QR)</a>
    </div>

    @if($extraId)
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        @if($subscriptions->isEmpty())
        <p class="text-center text-gray-500 py-8">Aucun élève actif inscrit à cet extra.</p>
        @else
        <form action="{{ route('extras.attendances.store') }}" method="POST">
            @csrf
            <input type="hidden" name="extra_id" value="{{ $extraId }}">
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 font-semibold text-gray-600">Élève</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Présent</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Absent</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Arrivée</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Départ</th>
                        <th class="text-center py-2 px-3 font-semibold text-gray-600">Dépassement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($subscriptions as $sub)
                    @php $existing = $sub->attendances->first(); @endphp
                    <tr>
                        <td class="py-2 px-3">
                            {{ $sub->student->last_name }} {{ $sub->student->first_name }}
                            @if($sub->student->dietary_restrictions)
                            <div class="text-xs text-orange-600">⚠️ {{ $sub->student->dietary_restrictions }}</div>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="radio" name="records[{{ $sub->id }}][status]" value="present" {{ !$existing || $existing->status === 'present' ? 'checked' : '' }} class="w-4 h-4 text-primary">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="radio" name="records[{{ $sub->id }}][status]" value="absent" {{ $existing && $existing->status === 'absent' ? 'checked' : '' }} class="w-4 h-4 text-red-600">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="time" name="records[{{ $sub->id }}][checked_in_at]" value="{{ $existing?->checked_in_at?->format('H:i') }}" class="px-2 py-1 border border-gray-300 rounded">
                        </td>
                        <td class="py-2 px-3 text-center">
                            <input type="time" name="records[{{ $sub->id }}][checked_out_at]" value="{{ $existing?->checked_out_at?->format('H:i') }}" class="px-2 py-1 border border-gray-300 rounded">
                        </td>
                        <td class="py-2 px-3 text-center">
                            @if($existing && $existing->overage_amount > 0)
                                @if($existing->overage_billed_at)
                                <span class="text-xs text-gray-500">✅ Facturé ({{ number_format($existing->overage_amount, 0, ',', ' ') }} FCFA)</span>
                                @else
                                <form action="{{ route('extras.attendances.bill-overage', $existing->id) }}" method="POST" onsubmit="return confirm('Facturer {{ number_format($existing->overage_amount, 0, ',', ' ') }} FCFA de dépassement ?')">
                                    @csrf
                                    <button type="submit" class="text-xs text-orange-600 hover:text-orange-800 font-semibold">⏱️ {{ number_format($existing->overage_amount, 0, ',', ' ') }} F — Facturer</button>
                                </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-lg font-semibold transition">✅ Enregistrer les présences</button>
            </div>
        </form>
        @endif
    </div>
    @endif
</div>
@endsection
