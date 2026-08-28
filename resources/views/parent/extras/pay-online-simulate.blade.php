@extends('layouts.app')

@section('title', 'Paiement en ligne (démo)')
@section('page_title', 'Paiement en ligne')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-white border-2 border-dashed border-purple-300 rounded-xl p-6 shadow-sm">
        <div class="text-center mb-6">
            <span class="text-4xl">🧪</span>
            <h1 class="text-xl font-bold text-gray-800 mt-2">Page de paiement — Mode démo</h1>
            <p class="text-sm text-purple-700 bg-purple-50 rounded-lg px-3 py-2 mt-3">
                Aucune clé API CinetPay n'est configurée pour cette école. Cette page remplace la vraie page de paiement CinetPay le temps de tester le parcours.
            </p>
        </div>

        <div class="border border-gray-200 rounded-lg p-4 mb-6 text-sm space-y-2">
            <div class="flex justify-between"><span class="text-gray-500">Service</span><span class="font-semibold text-gray-800">{{ $onlinePayment->subscription->extra->name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Élève</span><span class="font-semibold text-gray-800">{{ $student->first_name }} {{ $student->last_name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Référence</span><span class="font-mono text-xs text-gray-600">{{ $onlinePayment->transaction_id }}</span></div>
            <div class="flex justify-between border-t border-gray-100 pt-2 mt-2"><span class="text-gray-700 font-semibold">Montant</span><span class="font-bold text-primary text-lg">{{ number_format($onlinePayment->amount, 0, ',', ' ') }} FCFA</span></div>
        </div>

        <div class="grid grid-cols-1 gap-3">
            <form action="{{ route('parent.extras.pay-online.simulate.confirm', ['student' => $student->id, 'transaction' => $onlinePayment->transaction_id]) }}" method="POST">
                @csrf
                <input type="hidden" name="decision" value="success">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold transition">✅ Simuler un paiement réussi</button>
            </form>
            <form action="{{ route('parent.extras.pay-online.simulate.confirm', ['student' => $student->id, 'transaction' => $onlinePayment->transaction_id]) }}" method="POST">
                @csrf
                <input type="hidden" name="decision" value="fail">
                <button type="submit" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-3 rounded-lg font-semibold transition">❌ Simuler un échec / annulation</button>
            </form>
        </div>
    </div>
</div>
@endsection
