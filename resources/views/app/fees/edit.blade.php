@extends('layouts.app')

@section('title', 'Modifier le Frais')
@section('page_title', 'Modifier le Frais Scolaire')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('app.fees.update', $fee) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Année Scolaire *</label>
                        <select name="school_year_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('school_year_id') border-red-500 @enderror">
                            @foreach($schoolYears as $year)
                                <option value="{{ $year->id }}" {{ old('school_year_id', $fee->school_year_id) == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_year_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Niveau *</label>
                        <select name="level" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('level') border-red-500 @enderror">
                            @foreach($allowedLevels as $level)
                                <option value="{{ $level }}" {{ old('level', $fee->level) === $level ? 'selected' : '' }}>
                                    {{ $level }}
                                </option>
                            @endforeach
                        </select>
                        @error('level')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de frais *</label>
                        <select name="fee_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('fee_type') border-red-500 @enderror">
                            <option value="registration" {{ old('fee_type', $fee->fee_type) === 'registration' ? 'selected' : '' }}>Frais d'inscription</option>
                            <option value="tuition" {{ old('fee_type', $fee->fee_type) === 'tuition' ? 'selected' : '' }}>Frais de scolarité</option>
                        </select>
                        @error('fee_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA) *</label>
                        <input type="number" name="amount" value="{{ old('amount', $fee->amount) }}" required min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('amount') border-red-500 @enderror">
                        @error('amount')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <input type="text" name="description" value="{{ old('description', $fee->description) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('description') border-red-500 @enderror">
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                </div>
                
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Mettre à jour
                    </button>
                    <a href="{{ route('app.fees.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
@endsection