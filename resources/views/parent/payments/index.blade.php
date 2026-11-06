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

    <!-- SÉLECTEUR D'ENFANT (visible seulement s'il y a plus d'un enfant) -->
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

    {{-- Échéances : un clic sur une ligne affiche les paiements effectués sur cette échéance,
         avec la réédition de chaque reçu. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <h2 class="text-sm font-semibold text-gray-500 uppercase px-6 pt-5 pb-3">Échéances</h2>

        @if(count($installments))
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-2 sm:px-4 py-3 text-left">Échéance</th>
                            <th class="px-2 sm:px-4 py-3 text-left">Date limite</th>
                            <th class="px-2 sm:px-4 py-3 text-right hidden sm:table-cell">Montant</th>
                            <th class="px-2 sm:px-4 py-3 text-right hidden sm:table-cell">Payé</th>
                            <th class="px-2 sm:px-4 py-3 text-right">Reste</th>
                            <th class="px-2 sm:px-4 py-3 text-center">Statut</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    @foreach($installments as $installment)
                        @php
                            $reste = max(0, $installment->amount - $installment->paid_amount);
                            $isPaid = $reste <= 0;
                            $isLate = ! $isPaid && \Carbon\Carbon::parse($installment->due_date)->isPast();
                            $isPartial = ! $isPaid && $installment->paid_amount > 0;
                            $badgeClass = $isPaid ? 'bg-green-100 text-green-700' : ($isLate ? 'bg-red-100 text-red-700' : ($isPartial ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'));
                            $badgeText = $isPaid ? 'Payé' : ($isLate ? 'En retard' : ($isPartial ? 'Partiel' : 'En attente'));
                            $installmentPayments = $paymentsByInstallment->get($installment->id, collect());
                        @endphp
                        <tbody x-data="{ open: false }" class="border-t border-gray-100">
                            <tr @click="open = !open" class="cursor-pointer hover:bg-gray-50" title="Voir les paiements de cette échéance">
                                <td class="px-2 sm:px-4 py-3 font-semibold text-gray-800">{{ $installment->description }}</td>
                                <td class="px-2 sm:px-4 py-3 {{ $isLate ? 'text-red-600 font-semibold' : 'text-gray-700' }}">{{ \Carbon\Carbon::parse($installment->due_date)->translatedFormat('d M Y') }}</td>
                                <td class="px-2 sm:px-4 py-3 text-right whitespace-nowrap hidden sm:table-cell">{{ number_format($installment->amount, 0, ',', ' ') }}</td>
                                <td class="px-2 sm:px-4 py-3 text-right whitespace-nowrap text-green-700 hidden sm:table-cell">{{ number_format($installment->paid_amount, 0, ',', ' ') }}</td>
                                <td class="px-2 sm:px-4 py-3 text-right whitespace-nowrap font-semibold {{ $isPaid ? 'text-gray-400' : 'text-red-600' }}">{{ number_format($reste, 0, ',', ' ') }}</td>
                                <td class="px-2 sm:px-4 py-3 text-center"><span class="text-xs font-semibold px-2 py-1 rounded-full {{ $badgeClass }}">{{ $badgeText }}</span></td>
                                <td class="px-2 py-3 text-gray-400 text-center" x-text="open ? '▾' : '▸'">▸</td>
                            </tr>
                            <tr x-show="open" x-cloak>
                                <td colspan="7" class="bg-gray-50 px-4 py-3">
                                    @if($installmentPayments->isEmpty())
                                        <p class="text-sm text-gray-500">Aucun paiement enregistré sur cette échéance.</p>
                                    @else
                                        <table class="w-full text-sm">
                                            <thead class="text-xs text-gray-500">
                                                <tr>
                                                    <th class="text-left py-1">Date</th>
                                                    <th class="text-right py-1">Montant</th>
                                                    <th class="text-left py-1 pl-4 hidden sm:table-cell">Mode</th>
                                                    <th class="text-left py-1 pl-4 hidden sm:table-cell">Référence</th>
                                                    <th class="py-1"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($installmentPayments as $payment)
                                                    <tr class="border-t border-gray-200">
                                                        <td class="py-2">{{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d M Y') }}</td>
                                                        <td class="py-2 text-right font-semibold text-gray-800 whitespace-nowrap">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                                        <td class="py-2 pl-4 hidden sm:table-cell">{{ \App\Support\Labels::of($payment->payment_method) }}</td>
                                                        <td class="py-2 pl-4 hidden sm:table-cell text-gray-500">{{ $payment->reference ?: '—' }}</td>
                                                        <td class="py-2 text-right">
                                                            <a href="{{ route('parent.payments.receipt', ['student' => $student->id, 'payment' => $payment->id]) }}" target="_blank"
                                                               class="inline-flex items-center gap-1 bg-white border border-gray-300 hover:bg-primary hover:text-white hover:border-primary text-gray-700 px-3 py-1 rounded-lg text-xs font-semibold transition">
                                                                🧾 Rééditer le reçu
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @else
            <div class="text-center py-10 text-gray-500 border-t border-gray-100">
                <p>Aucune échéance n'a encore été établie pour cette année scolaire.</p>
            </div>
        @endif

        {{-- Paiements anciens rattachés à aucune échéance --}}
        @if($paymentsByInstallment->has(''))
            <div class="border-t border-gray-100 px-6 py-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Autres paiements</h3>
                @foreach($paymentsByInstallment->get('') as $payment)
                    <div class="flex justify-between items-center py-2 text-sm">
                        <span>{{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d M Y') }} — {{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                        <a href="{{ route('parent.payments.receipt', ['student' => $student->id, 'payment' => $payment->id]) }}" target="_blank" class="text-primary text-xs font-semibold">🧾 Rééditer le reçu</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection