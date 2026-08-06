@extends('layouts.app')

@section('title', 'Paiements - ' . $student->first_name)
@section('page_title', 'Suivi des Paiements')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">💳 Suivi des Paiements</h1>
            <p class="text-sm text-gray-500">Situation financière de {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
    </div>

    <!-- ✅ SÉLECTEUR D'ENFANT (visible seulement s'il y a plus d'un enfant) -->
    @if($siblings->count() > 1)
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <span class="font-semibold text-blue-800 text-sm">Vous avez plusieurs enfants. Sélectionnez celui dont vous voulez voir les paiements :</span>
        <select onchange="window.location.href = '/parent/' + this.value + '/payments'" class="block w-full sm:w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary rounded-md shadow-sm bg-white">
            @foreach($siblings as $sibling)
                <option value="{{ $sibling->id }}" {{ $student->id == $sibling->id ? 'selected' : '' }}>
                    {{ $sibling->first_name }} {{ $sibling->last_name }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <!-- Résumé Financier -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase mb-4">Situation financière globale</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Montant Attendu</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($totalExpected, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span></p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-xs text-green-600 uppercase tracking-wide mb-1">Déjà Payé</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format($totalPaid, 0, ',', ' ') }} <span class="text-sm font-normal text-green-600">FCFA</span></p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <p class="text-xs text-red-600 uppercase tracking-wide mb-1">Reste à Payer</p>
                <p class="text-2xl font-bold text-red-700">{{ number_format($remaining, 0, ',', ' ') }} <span class="text-sm font-normal text-red-600">FCFA</span></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Colonne Gauche : Échéances (2/3 de la largeur) -->
        <div class="lg:col-span-2">
            <h2 class="text-sm font-semibold text-gray-500 uppercase mb-3 px-1">Échéances à régler</h2>
            
            @forelse($installments as $installment)
                @php
                    $reste = $installment->amount - $installment->paid_amount;
                    $isLate = $reste > 0 && \Carbon\Carbon::parse($installment->due_date)->isPast();
                    $isPaid = $reste <= 0;
                    
                    $badgeClass = $isPaid ? 'bg-green-100 text-green-700' : ($isLate ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                    $badgeText = $isPaid ? 'Payé' : ($isLate ? 'En retard' : 'En attente');
                @endphp
                
                <div class="bg-white border border-gray-200 rounded-xl p-5 mb-3 shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                        <h3 class="font-bold text-gray-800">{{ $installment->description }}</h3>
                        <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $badgeClass }}">
                            {{ $badgeText }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs uppercase">Date d'échéance</p>
                            <p class="font-medium {{ $isLate ? 'text-red-600' : 'text-gray-800' }}">
                                {{ \Carbon\Carbon::parse($installment->due_date)->isoFormat('DD MMMM YYYY') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs uppercase">Montant total</p>
                            <p class="font-medium text-gray-800">{{ number_format($installment->amount, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>

                    @if(!$isPaid)
                        <div class="flex justify-between items-center text-red-600 font-semibold pt-3 mt-3 border-t border-gray-100">
                            <span>Reste à payer :</span>
                            <span class="text-lg">{{ number_format($reste, 0, ',', ' ') }} FCFA</span>
                        </div>
                    @else
                        <div class="flex justify-between items-center text-green-600 font-semibold pt-3 mt-3 border-t border-gray-100">
                            <span>Statut :</span>
                            <span>Soldé ✅</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200 border-dashed">
                    <p>Aucune échéance configurée pour le moment.</p>
                </div>
            @endforelse
        </div>

        <!-- Colonne Droite : Historique des Reçus (1/3 de la largeur) -->
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase mb-3 px-1">Historique des reçus</h2>
            
            @if($payments->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        @foreach($payments as $payment)
                            <div class="p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <p class="font-bold text-gray-800 text-sm">{{ $payment->description ?? 'Paiement' }}</p>
                                    <span class="text-sm font-bold text-blue-600">{{ number_format($payment->amount, 0, ',', ' ') }} F</span>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->isoFormat('DD MMM YYYY') }}
                                </p>
                                <a href="{{ route('parent.payments.receipt', ['student' => $student->id, 'payment' => $payment->id]) }}" 
                                   class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-primary hover:text-white text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Télécharger le reçu
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200 border-dashed">
                    <p class="text-sm">Aucun paiement enregistré.</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection