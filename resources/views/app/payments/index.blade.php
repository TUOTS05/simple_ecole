@extends('layouts.app')

@section('title', 'Paiements')
@section('page_title', 'Paiements')

@section('content')
    
    @if(session('payment_success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            <div class="font-semibold mb-2">✅ Paiement enregistré avec succès !</div>
            <div class="flex flex-col gap-2 text-sm">
                <a href="{{ session('payment_success.receipt_url') }}" target="_blank" rel="noopener noreferrer" class="flex items-center text-blue-100 hover:text-white font-bold underline">
                    📥 Télécharger le Reçu de Paiement
                </a>
                @if(session('payment_success.card_url'))
                    <a href="{{ session('payment_success.card_url') }}" target="_blank" rel="noopener noreferrer" class="flex items-center text-green-100 hover:text-white font-bold underline">
                        🪪 Télécharger la Carte Scolaire (avec QR Code)
                    </a>
                @endif
            </div>
        </div>
    @elseif(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
    @endif
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-3">
            <a href="{{ route('app.payments.export', request()->query()) }}" 
               class="inline-flex items-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exporter en Excel (CSV)
            </a>
            
            <a href="{{ route('app.payments.create') }}" 
               class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                + Nouveau Paiement
            </a>
        </div>
    </div>    <!-- Filtres -->
    <!-- <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('app.payments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="payment_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les types</option>
                    <option value="registration" {{ request('payment_type') === 'registration' ? 'selected' : '' }}>Inscription</option>
                    <option value="tuition" {{ request('payment_type') === 'tuition' ? 'selected' : '' }}>Scolarité</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Méthode</label>
                <select name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les méthodes</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="check" {{ request('payment_method') === 'check' ? 'selected' : '' }}>Chèque</option>
                    <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Virement</option>
                    <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="flex items-end space-x-2 md:col-span-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('app.payments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                    Réinitialiser
                </a>
            </div>
            
        </form>
    </div> -->
    

        <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('app.payments.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            
            <!-- ✅ NOUVEAU : Filtre par Classe -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="payment_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les types</option>
                    <option value="registration" {{ request('payment_type') === 'registration' ? 'selected' : '' }}>Inscription</option>
                    <option value="tuition" {{ request('payment_type') === 'tuition' ? 'selected' : '' }}>Scolarité</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Méthode</label>
                <select name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les méthodes</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="check" {{ request('payment_method') === 'check' ? 'selected' : '' }}>Chèque</option>
                    <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Virement</option>
                    <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <!-- Notez le md:col-span-5 pour s'aligner avec les 5 colonnes -->
            <div class="flex items-end space-x-2 md:col-span-5">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('app.payments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
                    Réinitialiser
                </a>
            </div>
            
        </form>
    </div>
    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Élève</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Classe</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Méthode</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Reçu par</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-semibold">
                            {{ $payment->enrollment->student->last_name }} {{ $payment->enrollment->student->first_name }}
                        </td>
                        <td class="py-3 px-4 text-sm">{{ $payment->enrollment->schoolClass->name ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $payment->payment_type === 'registration' ? 'bg-secondary text-gray-800' : 'bg-primary text-white' }}">
                                {{ $payment->payment_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-bold text-lg">{{ $payment->formatted_amount }}</td>
                        <td class="py-3 px-4 text-sm">
                            @if($payment->payment_method === 'cash') 💵 Espèces
                            @elseif($payment->payment_method === 'check') 📄 Chèque
                            @elseif($payment->payment_method === 'transfer') 🏦 Virement
                            @else 📱 Mobile Money
                            @endif
                        </td>
                        <td class="py-3 px-4 text-sm">{{ $payment->receivedBy->first_name }} {{ $payment->receivedBy->last_name }}</td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <form action="{{ route('app.payments.destroy', $payment) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr ? Les montants seront recalculés.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">
                            Aucun paiement trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($payments->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
    
@endsection
