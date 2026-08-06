@extends('layouts.app')

@section('title', 'Détails du Paiement')
@section('page_title', 'Détails du Paiement')

@section('content')
    
    <div class="max-w-4xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.payments.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">
                Paiement de {{ $payment->formatted_amount }}
            </h1>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Élève</p>
                    <p class="text-lg font-semibold">{{ $payment->enrollment->student->last_name }} {{ $payment->enrollment->student->first_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Année Scolaire</p>
                    <p class="text-lg font-semibold">{{ $payment->enrollment->schoolYear->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Type</p>
                    <p class="text-lg font-semibold">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $payment->payment_type === 'registration' ? 'bg-secondary text-gray-800' : 'bg-primary text-white' }}">
                            {{ $payment->payment_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Montant</p>
                    <p class="text-2xl font-bold text-primary">{{ $payment->formatted_amount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Date</p>
                    <p class="text-lg font-semibold">{{ $payment->payment_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Méthode</p>
                    <p class="text-lg font-semibold">
                        @if($payment->payment_method === 'cash') 💵 Espèces
                        @elseif($payment->payment_method === 'check') 📄 Chèque
                        @elseif($payment->payment_method === 'transfer') 🏦 Virement
                        @else 📱 Mobile Money
                        @endif
                    </p>
                </div>
                @if($payment->reference)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Référence</p>
                        <p class="text-lg font-semibold">{{ $payment->reference }}</p>
                    </div>
                @endif
                @if($payment->notes)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Notes</p>
                        <p class="text-lg">{{ $payment->notes }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-gray-600">Reçu par</p>
                    <p class="text-lg font-semibold">{{ $payment->receivedBy->first_name }} {{ $payment->receivedBy->last_name }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            <div class="flex space-x-4">
                <a href="{{ route('app.enrollments.show', $payment->enrollment) }}" 
                   class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                    👁️ Voir l'inscription
                </a>
                <form action="{{ route('app.payments.destroy', $payment) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr ? Les montants seront recalculés.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                        🗑️ Supprimer
                    </button>
                </form>
            </div>
        </div>
        
    </div>
    
@endsection