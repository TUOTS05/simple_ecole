@extends('layouts.app')

@section('title', 'Remboursements Extras')
@section('page_title', 'Remboursements Extras')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="extraRefundForm()">

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
        <h3 class="text-lg font-bold text-gray-800 mb-1">💸 Enregistrer un remboursement</h3>
        <p class="text-sm text-gray-500 mb-4">Ex : un élève quitte le service en cours de mois — le montant suggéré est calculé au prorata de la période non consommée.</p>
        @if($refundableSubscriptions->isEmpty())
        <p class="text-gray-500">Aucune inscription avec un montant payé pour cette année.</p>
        @else
        <form action="{{ route('extras.refunds.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Élève / Extra *</label>
                <select name="extra_subscription_id" x-model="subscriptionId" @change="fetchSuggested" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">-- Choisir --</option>
                    @foreach($refundableSubscriptions as $sub)
                    <option value="{{ $sub->id }}">
                        {{ $sub->student->last_name }} {{ $sub->student->first_name }} — {{ $sub->extra->name }} (payé {{ number_format($sub->paid_amount, 0, ',', ' ') }} FCFA)
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                <input type="number" name="amount" x-model="amount" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <p class="text-xs text-gray-500 mt-1" x-show="loading">Calcul du prorata...</p>
                <p class="text-xs text-purple-600 mt-1" x-show="!loading && suggested > 0">💡 Suggéré : <span x-text="suggested"></span> FCFA</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode *</label>
                <select name="refund_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="cash">Espèces</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="transfer">Virement</option>
                    <option value="check">Chèque</option>
                    <option value="credit_note">Avoir</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motif</label>
                <input type="text" name="reason" maxlength="255" placeholder="Ex : départ le 15/09" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-center gap-2 md:col-span-2">
                <input type="checkbox" name="terminate_subscription" value="1" id="terminate_subscription" checked class="w-4 h-4 text-primary border-gray-300 rounded">
                <label for="terminate_subscription" class="text-sm text-gray-700">Résilier ce service suite au remboursement</label>
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
        <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Historique des remboursements</h3>

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
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Motif</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Traité par</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($refunds as $refund)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $refund->processed_at->format('d/m/Y') }}</td>
                    <td class="py-3 px-4 font-medium text-gray-800">{{ $refund->subscription->student->last_name }} {{ $refund->subscription->student->first_name }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $refund->subscription->extra->name }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-red-600">-{{ number_format($refund->amount, 0, ',', ' ') }} FCFA</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $refund->reason ?: '—' }}</td>
                    <td class="py-3 px-4 text-sm text-gray-600">{{ $refund->processedBy->full_name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-gray-500">Aucun remboursement enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4">{{ $refunds->links() }}</div>
    </div>
</div>

@push('scripts')
<script>
    function extraRefundForm() {
        return {
            subscriptionId: '',
            amount: '',
            suggested: 0,
            loading: false,

            async fetchSuggested() {
                this.suggested = 0;
                this.amount = '';
                if (!this.subscriptionId) return;
                this.loading = true;
                try {
                    const response = await fetch(`/extras/refunds/${this.subscriptionId}/suggested`);
                    const data = await response.json();
                    this.suggested = data.suggested_amount;
                    if (this.suggested > 0) this.amount = this.suggested;
                } catch (error) { console.error(error); } finally { this.loading = false; }
            },
        };
    }
</script>
@endpush
@endsection
