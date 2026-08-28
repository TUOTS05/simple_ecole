@extends('layouts.app')

@section('title', 'Mes Extras - ' . $student->first_name)
@section('page_title', 'Mes Extras')

@php
$statusLabels = [
    'requested' => 'Demande envoyée',
    'pending' => 'En attente',
    'validated' => 'Validée',
    'active' => 'Actif',
    'waitlisted' => "Sur liste d'attente",
    'suspended' => 'Suspendu',
    'terminated' => 'Refusé / Résilié',
    'completed' => 'Terminé',
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
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🧩 Mes Extras</h1>
            <p class="text-sm text-gray-500">Services souscrits pour {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <a href="{{ route('parent.extras.catalogue', $student->id) }}" class="bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-lg font-semibold text-sm transition text-center">
            + Demander un nouveau service
        </a>
    </div>

    @if($siblings->count() > 1)
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <span class="font-semibold text-blue-800 text-sm">Sélectionnez un enfant :</span>
        <select onchange="window.location.href = '/parent/' + this.value + '/extras'" class="block w-full sm:w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary rounded-md shadow-sm bg-white">
            @foreach($siblings as $sibling)
                <option value="{{ $sibling->id }}" {{ $student->id == $sibling->id ? 'selected' : '' }}>{{ $sibling->first_name }} {{ $sibling->last_name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    @if($monthlyTotal > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Montant mensuel des services actifs</p>
        <p class="text-3xl font-bold text-primary">{{ number_format($monthlyTotal, 0, ',', ' ') }} FCFA</p>
    </div>
    @endif

    @forelse($subscriptions as $sub)
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-4 shadow-sm hover:shadow-md transition">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
            <h3 class="font-bold text-gray-800">{{ $sub->extra->category->icon ?? '🧩' }} {{ $sub->extra->name }}</h3>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-{{ $statusColors[$sub->status] ?? 'gray' }}-100 text-{{ $statusColors[$sub->status] ?? 'gray' }}-700">
                {{ $statusLabels[$sub->status] ?? ucfirst($sub->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-3">
            <div>
                <p class="text-gray-500 text-xs uppercase">Total dû</p>
                <p class="font-medium text-gray-800">{{ number_format($sub->total_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase">Payé</p>
                <p class="font-medium text-green-700">{{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase">Reste à payer</p>
                <p class="font-medium text-red-700">{{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>

        @if($sub->payments->isNotEmpty())
        <div class="border-t border-gray-100 pt-3 mt-3">
            <p class="text-xs text-gray-500 uppercase mb-2">Derniers paiements</p>
            <div class="flex flex-wrap gap-2">
                @foreach($sub->payments->take(5) as $payment)
                <a href="{{ route('parent.extras.payments.receipt', ['student' => $student->id, 'subscriptionId' => $sub->id, 'paymentId' => $payment->id]) }}"
                   class="text-xs bg-gray-100 hover:bg-primary hover:text-white text-gray-700 px-3 py-1.5 rounded-full transition">
                    📄 {{ number_format($payment->amount, 0, ',', ' ') }} F — {{ $payment->payment_date->format('d/m/Y') }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($sub->status === 'active')
        <div class="flex flex-wrap items-center gap-4 mt-3">
            @if($sub->remaining_amount > 0)
            <form action="{{ route('parent.extras.pay-online', ['student' => $student->id, 'subscriptionId' => $sub->id]) }}" method="POST" onsubmit="return confirm('Payer {{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA en ligne pour « {{ $sub->extra->name }} » ?')">
                @csrf
                <input type="hidden" name="amount" value="{{ $sub->remaining_amount }}">
                <button type="submit" class="text-xs bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-full font-semibold transition">
                    💳 Payer {{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA en ligne
                </button>
            </form>
            @endif
            <form action="{{ route('parent.extras.suspend', ['student' => $student->id, 'subscriptionId' => $sub->id]) }}" method="POST" onsubmit="return confirm('Demander la suspension de ce service ?')">
                @csrf
                <button type="submit" class="text-xs text-orange-600 hover:text-orange-800 font-semibold">⏸️ Demander une suspension</button>
            </form>
        </div>
        @endif
    </div>
    @empty
    <div class="text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200 border-dashed">
        <p>Aucun extra souscrit pour le moment.</p>
        <a href="{{ route('parent.extras.catalogue', $student->id) }}" class="text-primary underline text-sm">Découvrir les services disponibles</a>
    </div>
    @endforelse
</div>
@endsection
