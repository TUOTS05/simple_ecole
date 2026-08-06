@extends('layouts.app')

@section('title', 'Paiements Cantine')
@section('page_title', 'Paiements de la Cantine')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Formulaire de Paiement -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💳 Enregistrer un Paiement</h3>
        <form action="{{ route('app.canteen.payments.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $schoolYearId }}">

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève (Abonnement) *</label>
                <select name="canteen_subscription_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="">-- Choisir --</option>
                    @foreach($subscriptions as $sub)
                        <option value="{{ $sub->id }}">
                            {{ $sub->student->last_name }} {{ $sub->student->first_name }} - Reste: {{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                <input type="number" name="amount" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode *</label>
                <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="cash">Espèces</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="transfer">Virement</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition">
                    💰 Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- Historique des Paiements -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">📜 Historique des Paiements</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-green-600">Montant</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Mode</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Référence</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Reçu par</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm text-gray-800">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">
                        {{ $payment->subscription->student->last_name }} {{ $payment->subscription->student->first_name }}
                    </td>
                    <td class="py-3 px-4 text-right text-green-700 font-bold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                    <td class="py-3 px-4 text-sm font-mono text-gray-600">{{ $payment->reference ?? '-' }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->receivedByUser->name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">Aucun paiement enregistré.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($payments->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection