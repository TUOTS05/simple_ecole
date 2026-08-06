@extends('layouts.app')

@section('title', 'Modifier le Paiement')
@section('page_title', 'Modifier le Paiement')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('app.payments.update', $payment) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Inscription</label>
                        <input type="text" value="{{ $payment->enrollment->student->last_name }} {{ $payment->enrollment->student->first_name }} - {{ $payment->enrollment->schoolYear->name }}" disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <input type="text" value="{{ $payment->payment_type === 'registration' ? 'Frais d\'inscription' : 'Frais de scolarité' }}" disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant</label>
                        <input type="text" value="{{ $payment->formatted_amount }}" disabled
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Référence</label>
                        <input type="text" name="reference" value="{{ old('reference', $payment->reference) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('reference') border-red-500 @enderror">
                        @error('reference')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes', $payment->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                </div>
                
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Mettre à jour
                    </button>
                    <a href="{{ route('app.payments.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
@endsection