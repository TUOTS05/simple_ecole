@extends('layouts.app')

@section('title', 'Frais Scolaires')
@section('page_title', 'Frais Scolaires')

@section('content')
    
    @if(session('success'))
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
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Frais Scolaires</h1>
            <p class="text-gray-600 mt-1">Configurez les frais par niveau et année scolaire</p>
        </div>
        <a href="{{ route('app.fees.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouveau Frais
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('app.fees.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année Scolaire</label>
                <select name="school_year_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Toutes les années</option>
                    @foreach($schoolYears as $year)
                        <option value="{{ $year->id }}" {{ request('school_year_id') == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de frais</label>
                <select name="fee_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Tous les types</option>
                    <option value="registration" {{ request('fee_type') === 'registration' ? 'selected' : '' }}>Frais d'inscription</option>
                    <option value="tuition" {{ request('fee_type') === 'tuition' ? 'selected' : '' }}>Frais de scolarité</option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                    Filtrer
                </button>
                <a href="{{ route('app.fees.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
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
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Année</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Niveau</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Montant</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Description</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fees as $fee)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm">{{ $fee->schoolYear->name }}</td>
                        <td class="py-3 px-4 font-semibold">{{ $fee->level }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $fee->fee_type === 'registration' ? 'bg-secondary text-gray-800' : 'bg-primary text-white' }}">
                                {{ $fee->fee_type === 'registration' ? '📝 Inscription' : '📚 Scolarité' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-bold text-lg">{{ $fee->formatted_amount }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $fee->description ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.fees.show', $fee) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <a href="{{ route('app.fees.edit', $fee) }}" class="text-yellow-600 hover:text-yellow-800">
                                    ✏️
                                </a>
                                <form action="{{ route('app.fees.destroy', $fee) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Êtes-vous sûr ?')">
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
                        <td colspan="6" class="py-8 text-center text-gray-500">
                            Aucun frais configuré
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
@endsection