@extends('layouts.app')

@section('title', 'Détails du Frais')
@section('page_title', 'Détails du Frais')

@section('content')
    
    <div class="max-w-4xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.fees.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">
                {{ $fee->level }} - {{ $fee->fee_type === 'registration' ? 'Frais d\'inscription' : 'Frais de scolarité' }}
            </h1>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Année Scolaire</p>
                    <p class="text-lg font-semibold">{{ $fee->schoolYear->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Niveau</p>
                    <p class="text-lg font-semibold">{{ $fee->level }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Type</p>
                    <p class="text-lg font-semibold">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $fee->fee_type === 'registration' ? 'bg-secondary text-gray-800' : 'bg-primary text-white' }}">
                            {{ $fee->fee_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Montant</p>
                    <p class="text-2xl font-bold text-primary">{{ $fee->formatted_amount }}</p>
                </div>
                @if($fee->description)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Description</p>
                        <p class="text-lg">{{ $fee->description }}</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            <div class="flex space-x-4">
                <a href="{{ route('app.fees.edit', $fee) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    ✏️ Modifier
                </a>
                <form action="{{ route('app.fees.destroy', $fee) }}" method="POST" 
                      onsubmit="return confirm('Êtes-vous sûr ?')">
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