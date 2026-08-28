@extends('layouts.app')

@section('title', 'Inscriptions Extras')
@section('page_title', 'Inscriptions aux Extras')

@php
$statusLabels = [
    'requested' => 'Demande',
    'pending' => 'En attente',
    'validated' => 'Validée',
    'active' => 'Active',
    'waitlisted' => "Liste d'attente",
    'suspended' => 'Suspendue',
    'terminated' => 'Résiliée',
    'completed' => 'Terminée',
];
$statusColors = [
    'requested' => 'yellow',
    'pending' => 'yellow',
    'validated' => 'blue',
    'active' => 'green',
    'waitlisted' => 'purple',
    'suspended' => 'orange',
    'terminated' => 'red',
    'completed' => 'gray',
];
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Tous</option>
                    @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ $status == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('extras.subscriptions.pdf', ['school_year_id' => $schoolYearId, 'extra_id' => $extraId, 'status' => $status]) }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition text-sm">📄 Exporter PDF</a>
            <a href="{{ route('extras.subscriptions.create', ['school_year_id' => $schoolYearId]) }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">+ Inscrire des élèves</a>
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
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $sub)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ $sub->student->last_name }} {{ $sub->student->first_name }}
                        <div class="text-xs text-gray-500 font-mono">{{ $sub->student->matricule }}</div>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        {{ $sub->extra->category->icon ?? '' }} {{ $sub->extra->name }}
                        <div class="text-xs text-gray-400">{{ $sub->extraTarif->schoolClass->name ?? 'Toutes classes' }}</div>
                        @if($sub->extra->requires_parental_authorization)
                        <div class="text-xs {{ $sub->parental_authorization_signed ? 'text-green-600' : 'text-orange-600' }}">
                            {{ $sub->parental_authorization_signed ? '✅ Autorisation reçue' : '✍️ Autorisation en attente' }}
                        </div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right text-gray-800">
                        {{ number_format($sub->total_amount, 0, ',', ' ') }} FCFA
                        @if($sub->hasDiscount())
                        <div class="text-xs text-purple-600" title="{{ $sub->discount_reason }}">🏷️ -{{ number_format($sub->discount_amount, 0, ',', ' ') }} FCFA</div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right text-green-700 font-semibold">{{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-right text-red-700 font-semibold">{{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-center">
                        <span class="bg-{{ $statusColors[$sub->status] ?? 'gray' }}-100 text-{{ $statusColors[$sub->status] ?? 'gray' }}-700 px-2 py-1 rounded-full text-xs font-bold">
                            {{ $statusLabels[$sub->status] ?? ucfirst($sub->status) }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if($sub->status === 'active')
                        <a href="{{ route('extras.subscriptions.qrcode', $sub->id) }}" target="_blank" class="text-gray-500 hover:text-primary text-sm mr-2" title="Badge QR">🔳</a>
                        @endif
                        @if($sub->extra->requires_parental_authorization)
                        <a href="{{ route('extras.subscriptions.authorization-pdf', $sub->id) }}" target="_blank" class="text-gray-500 hover:text-primary text-sm mr-2" title="Imprimer l'autorisation">🖨️</a>
                        <form action="{{ route('extras.subscriptions.toggle-authorization', $sub->id) }}" method="POST" class="inline mr-2">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-sm {{ $sub->parental_authorization_signed ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $sub->parental_authorization_signed ? 'Annuler' : 'Marquer reçue' }}">
                                {{ $sub->parental_authorization_signed ? '↩️' : '✅' }}
                            </button>
                        </form>
                        @endif
                        @if(in_array($sub->status, ['requested', 'pending']))
                        <form action="{{ route('extras.subscriptions.validate', $sub->id) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="accept">
                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm mr-2">✅ Accepter</button>
                        </form>
                        <form action="{{ route('extras.subscriptions.validate', $sub->id) }}" method="POST" class="inline" onsubmit="return confirm('Refuser cette demande ?')">
                            @csrf @method('PATCH')
                            <input type="hidden" name="decision" value="refuse">
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">❌ Refuser</button>
                        </form>
                        @elseif($sub->status === 'waitlisted')
                        <form action="{{ route('extras.subscriptions.promote', $sub->id) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-purple-600 hover:text-purple-800 text-sm mr-2">⬆️ Promouvoir</button>
                        </form>
                        <form action="{{ route('extras.subscriptions.destroy', $sub->id) }}" method="POST" class="inline" onsubmit="return confirm('Retirer cet élève de la liste d\'attente ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Retirer</button>
                        </form>
                        @else
                        <form action="{{ route('extras.subscriptions.destroy', $sub->id) }}" method="POST" class="inline" onsubmit="return confirm('Annuler cette inscription ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Annuler</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">Aucune inscription trouvée pour ces filtres.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
