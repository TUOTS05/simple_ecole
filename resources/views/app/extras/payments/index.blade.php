@extends('layouts.app')

@section('title', 'Paiements Extras')
@section('page_title', 'Paiements Extras')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ subscriptionId: '', remaining: 0 }">

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

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">💳 Enregistrer un paiement</h3>
        @if($unpaidSubscriptions->isEmpty())
        <p class="text-gray-500">Aucune inscription active avec un reste à payer pour cette année.</p>
        @else
        <form action="{{ route('extras.payments.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève / Extra *</label>
                <select name="extra_subscription_id" x-model="subscriptionId"
                    @change="remaining = $event.target.selectedOptions[0].dataset.remaining"
                    required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($unpaidSubscriptions as $sub)
                    <option value="{{ $sub->id }}" data-remaining="{{ $sub->remaining_amount }}">
                        {{ $sub->student->last_name }} {{ $sub->student->first_name }} — {{ $sub->extra->name }} (reste {{ number_format($sub->remaining_amount, 0, ',', ' ') }} FCFA)
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                <input type="number" name="amount" required min="1" :max="remaining || null" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode *</label>
                <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="cash">Espèces</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="transfer">Virement</option>
                    <option value="check">Chèque</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
                <input type="text" name="reference" maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" maxlength="500" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">Enregistrer</button>
            </div>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Historique des paiements</h3>

        <form method="GET" class="flex flex-wrap gap-4 items-end mb-6">
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

        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Extra</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Mode</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Encaissé par</th>
                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-600">Reçu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">{{ $payment->subscription->student->last_name }} {{ $payment->subscription->student->first_name }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->subscription->extra->name }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-primary">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ ucfirst($payment->payment_method) }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $payment->receivedByUser->full_name ?? '—' }}</td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('extras.payments.receipt', $payment->id) }}" class="text-primary hover:text-primary-dark">📄</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-12 text-center text-gray-500">Aucun paiement enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
