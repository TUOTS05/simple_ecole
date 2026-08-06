@extends('layouts.app')

@section('title', 'Années Scolaires')
@section('page_title', 'Années Scolaires')

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
            <h1 class="text-3xl font-bold text-gray-800">Années Scolaires</h1>
            <p class="text-gray-600 mt-1">Gérez les années scolaires de votre école</p>
        </div>
        <a href="{{ route('app.school-years.create') }}" 
           class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouvelle Année
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Début</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Fin</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Statut</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schoolYears as $year)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">{{ $year->name }}</td>
                        <td class="py-3 px-4 text-sm">{{ $year->start_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-sm">{{ $year->end_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">
                            @if($year->is_active)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-accent text-white">
                                    ✅ Active
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-300 text-gray-700">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.school-years.show', $year) }}" class="text-blue-600 hover:text-blue-800">
                                    👁️
                                </a>
                                <a href="{{ route('app.school-years.edit', $year) }}" class="text-yellow-600 hover:text-yellow-800">
                                    ✏️
                                </a>
                                <form action="{{ route('app.school-years.destroy', $year) }}" method="POST" class="inline" 
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
                        <td colspan="5" class="py-8 text-center text-gray-500">
                            Aucune année scolaire créée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
@endsection