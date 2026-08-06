@extends('layouts.app')

@section('title', $schoolYear->name)
@section('page_title', $schoolYear->name)

@section('content')
    
    <div class="max-w-4xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('app.school-years.index') }}" class="text-primary hover:text-primary-dark font-semibold">
                ← Retour
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $schoolYear->name }}</h1>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Date de début</p>
                    <p class="text-lg font-semibold">{{ $schoolYear->start_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Date de fin</p>
                    <p class="text-lg font-semibold">{{ $schoolYear->end_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Statut</p>
                    <p class="text-lg font-semibold">
                        @if($schoolYear->is_active)
                            <span class="text-accent">✅ Active</span>
                        @else
                            <span class="text-gray-500">Inactive</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Frais configurés</p>
                    <p class="text-lg font-semibold">{{ $schoolYear->fees_count }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Inscriptions</p>
                    <p class="text-lg font-semibold">{{ $schoolYear->enrollments_count }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
            <div class="flex space-x-4">
                <a href="{{ route('app.school-years.edit', $schoolYear) }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    ✏️ Modifier
                </a>
                <form action="{{ route('app.school-years.destroy', $schoolYear) }}" method="POST" 
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