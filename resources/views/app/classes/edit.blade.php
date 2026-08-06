@extends('layouts.app')

@section('title', 'Modifier la classe')
@section('page_title', 'Modifier la classe')

@section('content')
    
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            
            <form action="{{ route('app.classes.update', $class) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    
                    <!-- Nom -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">🏷️</span> Nom de la classe *
                        </label>
                        <input type="text" name="name" value="{{ old('name', $class->name) }}" required
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
                                <option value="maternelle" {{ old('cycle', $class->cycle) === 'maternelle' ? 'selected' : '' }}>🧒 Maternelle</option>
                            @endif
                            @if($school->isPrimaire() || $school->isBoth())
                                <option value="primaire" {{ old('cycle', $class->cycle) === 'primaire' ? 'selected' : '' }}>📚 Primaire</option>
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
                            <option value="">-- Sélectionner un niveau --</option>
                        </select>
                        @error('level')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Capacité -->
                    <div>
                        <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                            <span class="mr-2">👥</span> Capacité
                        </label>
                        <input type="number" name="capacity" value="{{ old('capacity', $class->capacity) }}" min="1" max="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('capacity') border-red-500 @enderror">
                        @error('capacity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                </div>
                
                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Mettre à jour
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
        const currentLevel = '{{ old('level', $class->level) }}';
        
        const cycleSelect = document.getElementById('cycle');
        const levelSelect = document.getElementById('level');
        
        function updateLevels() {
            const cycle = cycleSelect.value;
            levelSelect.innerHTML = '<option value="">-- Sélectionner un niveau --</option>';
            
            if (cycle && levelsByCycle[cycle]) {
                levelsByCycle[cycle].forEach(level => {
                    const option = document.createElement('option');
                    option.value = level;
                    option.textContent = level;
                    if (level === currentLevel) option.selected = true;
                    levelSelect.appendChild(option);
                });
            }
        }
        
        cycleSelect.addEventListener('change', updateLevels);
        updateLevels();
    </script>
    
@endsection