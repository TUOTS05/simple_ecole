@extends('layouts.app')

@section('title', 'Nouvelle Classe')
@section('page_title', 'Nouvelle Classe')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('app.classes.store') }}" method="POST">
                @csrf
                
                <div class="space-y-6">
                    
                    <!-- Nom -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">🏷️</span> Nom de la classe *
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required 
                               placeholder="Ex: GS A, CP1 B, CM2..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Cycle -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">🎓</span> Cycle *
                        </label>
                        <select name="cycle" id="cycle" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('cycle') border-red-500 @enderror">
                            <option value="">-- Sélectionner un cycle --</option>
                            @if($school->isMaternelle() || $school->isBoth())
                                <option value="maternelle" {{ old('cycle') === 'maternelle' ? 'selected' : '' }}>🧒 Maternelle</option>
                            @endif
                            @if($school->isPrimaire() || $school->isBoth())
                                <option value="primaire" {{ old('cycle') === 'primaire' ? 'selected' : '' }}>📚 Primaire</option>
                            @endif
                        </select>
                        @error('cycle')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Niveau -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">📊</span> Niveau *
                        </label>
                        <select name="level" id="level" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('level') border-red-500 @enderror">
                            <option value="">-- Sélectionnez d'abord un cycle --</option>
                        </select>
                        @error('level')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Capacité -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">👥</span> Capacité (optionnel)
                        </label>
                        <input type="number" name="capacity" value="{{ old('capacity') }}" min="1" max="100"
                               placeholder="Ex: 30"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('capacity') border-red-500 @enderror">
                        @error('capacity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Nombre maximum d'élèves dans cette classe</p>
                    </div>
                    
                </div>
                
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Créer la classe
                    </button>
                    <a href="{{ route('app.classes.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <script>
        const levelsByCycle = @json($levelsByCycle);
        
        const cycleSelect = document.getElementById('cycle');
        const levelSelect = document.getElementById('level');
        
        cycleSelect.addEventListener('change', function() {
            const cycle = this.value;
            levelSelect.innerHTML = '<option value="">-- Sélectionner un niveau --</option>';
            
            if (cycle && levelsByCycle[cycle]) {
                levelsByCycle[cycle].forEach(level => {
                    const option = document.createElement('option');
                    option.value = level;
                    option.textContent = level;
                    levelSelect.appendChild(option);
                });
            }
        });
        
        // Restaurer l'ancien niveau si erreur de validation
        const oldLevel = '{{ old('level') }}';
        const oldCycle = '{{ old('cycle') }}';
        if (oldCycle && oldLevel) {
            cycleSelect.value = oldCycle;
            cycleSelect.dispatchEvent(new Event('change'));
            setTimeout(() => {
                levelSelect.value = oldLevel;
            }, 100);
        }
    </script>
    
@endsection