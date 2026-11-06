@extends('layouts.app')

@section('title', 'Avenant au contrat')
@section('page_title', 'Avenant au contrat de ' . $contract->school->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-6">

        <!-- Contrat modifié -->
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded">
            <p class="text-yellow-900 font-semibold">✍️ Avenant au contrat {{ $contract->contract_number }}</p>
            <p class="text-sm text-yellow-800 mt-1">
                Offre actuelle : <strong>{{ $contract->plan_name }}</strong> —
                {{ number_format($contract->amount, 0, ',', ' ') }} FCFA,
                du {{ $contract->start_date->format('d/m/Y') }} au {{ $contract->end_date->format('d/m/Y') }}.
            </p>
            <p class="text-sm text-yellow-800">
                Le contrat signé n'est pas modifié : il prend fin la veille de la date d'effet, et l'avenant
                s'applique jusqu'au {{ $contract->end_date->format('d/m/Y') }}.
            </p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded p-4 mb-6 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('superadmin.subscriptions.store-amendment', $contract) }}" method="POST">
            @csrf

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouvelle offre *</label>
                        <select name="plan_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id || (! old('plan_id') && $plan->name === $contract->plan_name))>
                                    {{ $plan->name }} — {{ number_format($plan->yearly_price, 0, ',', ' ') }} FCFA/an
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'effet *</label>
                        <input type="date" name="effective_date" required
                            value="{{ old('effective_date', today()->max($contract->start_date->copy()->addDay())->format('Y-m-d')) }}"
                            min="{{ $contract->start_date->copy()->addDay()->format('Y-m-d') }}"
                            max="{{ $contract->end_date->format('Y-m-d') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Montant facturé pour la période restante (FCFA) *</label>
                    <input type="number" name="amount" required min="0" value="{{ old('amount', $contract->amount) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motif de l'avenant *</label>
                    <textarea name="reason" required rows="3" maxlength="500"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                        placeholder="Ex. passage à l'offre supérieure suite à l'ouverture d'une classe">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-4">
                <a href="{{ route('superadmin.subscriptions.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">Annuler</a>
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg font-semibold transition shadow-md">
                    Établir l'avenant
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
