@extends('layouts.app')

@section('title', 'Détails Inscription')
@section('page_title', 'Détails de l\'inscription')

@section('content')
    
    <div class="max-w-6xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.enrollments.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour
            </a>
        </div>
        
        <!-- Carte principale -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        {{ $enrollment->student->last_name }} {{ $enrollment->student->first_name }}
                    </h1>
                    <p class="text-gray-600">
                        {{ $enrollment->schoolYear->name }} 
                        @if($enrollment->schoolClass)
                            - {{ $enrollment->schoolClass->name }}
                        @endif
                    </p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold
                    {{ $enrollment->status === 'enrolled' ? 'bg-accent text-white' : 
                       ($enrollment->status === 'reserved' ? 'bg-secondary text-gray-800' : 'bg-danger text-white') }}">
                    @if($enrollment->status === 'enrolled') ✅ Inscrit
                    @elseif($enrollment->status === 'reserved') 📝 Réservé
                    @else ❌ Retiré
                    @endif
                </span>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Date d'inscription</p>
                    <p class="text-lg font-semibold">{{ $enrollment->enrollment_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Frais d'inscription</p>
                    <p class="text-lg font-semibold">
                        @if($enrollment->registration_fee_paid)
                            <span class="text-accent">✅ Payé</span>
                        @else
                            <span class="text-danger">❌ Non payé</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Statut global</p>
                    <p class="text-lg font-semibold">
                        @if($enrollment->isFullyPaid())
                            <span class="text-accent">✅ Tout payé</span>
                        @else
                            <span class="text-warning">⏳ En cours</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Statistiques financières -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-accent">
                <p class="text-sm text-gray-600 mb-1">Total Scolarité</p>
                <p class="text-3xl font-bold text-gray-800">
                    {{ number_format($enrollment->tuition_fee_total, 0, ',', ' ') }} FCFA
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-primary">
                <p class="text-sm text-gray-600 mb-1">Déjà Payé</p>
                <p class="text-3xl font-bold text-accent">
                    {{ number_format($enrollment->tuition_fee_paid, 0, ',', ' ') }} FCFA
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-danger">
                <p class="text-sm text-gray-600 mb-1">Reste à Payer</p>
                <p class="text-3xl font-bold text-danger">
                    {{ number_format($enrollment->tuition_fee_remaining, 0, ',', ' ') }} FCFA
                </p>
            </div>
            
        </div>
        
        <!-- Bouton Ajouter Paiement -->
        @if($enrollment->tuition_fee_remaining > 0 || !$enrollment->registration_fee_paid)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Actions de paiement</h2>
                <div class="flex space-x-4">
                    @if(!$enrollment->registration_fee_paid)
                        <a href="{{ route('app.payments.create', ['enrollment_id' => $enrollment->id, 'payment_type' => 'registration']) }}" 
                           class="bg-secondary hover:bg-yellow-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                            📝 Payer Frais d'inscription
                        </a>
                    @endif
                    
                    @if($enrollment->tuition_fee_remaining > 0)
                        <a href="{{ route('app.payments.create', ['enrollment_id' => $enrollment->id, 'payment_type' => 'tuition']) }}" 
                           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                            💰 Payer Scolarité
                        </a>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Historique des paiements -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Historique des paiements</h2>
            
            @if($enrollment->payments->count() > 0)
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Méthode</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Référence</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Reçu par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollment->payments->sortByDesc('payment_date') as $payment)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 text-sm">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        {{ $payment->payment_type === 'registration' ? 'bg-secondary text-gray-800' : 'bg-primary text-white' }}">
                                        {{ $payment->payment_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-bold text-lg">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="py-3 px-4 text-sm">
                                    @if($payment->payment_method === 'cash') 💵 Espèces
                                    @elseif($payment->payment_method === 'check') 📄 Chèque
                                    @elseif($payment->payment_method === 'transfer') 🏦 Virement
                                    @else 📱 Mobile Money
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-sm">{{ $payment->reference ?? '—' }}</td>
                                <td class="py-3 px-4 text-sm">{{ $payment->receivedBy->first_name }} {{ $payment->receivedBy->last_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-center text-gray-500 py-8">Aucun paiement enregistré</p>
            @endif
        </div>
        
    </div>
    
@endsection