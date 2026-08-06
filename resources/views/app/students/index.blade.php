@extends('layouts.app')

@section('title', 'Liste des Élèves')
@section('page_title', 'Gestion des Élèves')

@section('content')
<div class="max-w-7xl mx-auto">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Liste des Élèves</h1>
            <p class="text-gray-600 mt-1">Gérez et exportez la liste des élèves.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @php
            // On récupère les paramètres actuels (comme class_id) pour les garder dans l'export
            $currentParams = request()->query();
            @endphp

            <!-- Bouton Export Excel -->
            <a href="{{ route('app.students.export.excel', $currentParams) }}"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📗 Export Excel
            </a>

            <!-- Bouton Export PDF -->
            <a href="{{ route('app.students.export.pdf', $currentParams) }}"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                📕 Export PDF
            </a>

            <!-- Bouton Nouvel Élève -->
            <a href="{{ route('app.students.create') }}"
                class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2 text-sm shadow">
                + Nouvel Élève
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('app.students.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Année Scolaire</label>
                <select name="school_year_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Toutes les années</option>
                    @foreach($schoolYears as $year)
                    <option value="{{ $year->id }}" {{ request('school_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Classe</label>
                <select name="class_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Section</label>
                <select name="section" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Toutes les sections</option>
                    <option value="A" {{ request('section') == 'A' ? 'selected' : '' }}>Section A</option>
                    <option value="B" {{ request('section') == 'B' ? 'selected' : '' }}>Section B</option>
                    <option value="C" {{ request('section') == 'C' ? 'selected' : '' }}>Section C</option>
                </select>
            </div>

            <div class="lg:col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, Prénom ou Matricule" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 text-sm font-semibold rounded-lg hover:bg-primary-dark transition flex items-center justify-center">
                    Filtrer
                </button>
                <a href="{{ route('app.students.index') }}" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 text-sm font-semibold rounded-lg hover:bg-gray-300 transition flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Matricule</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom et Prénom</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($students as $student)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $student->matricule ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold mr-3">
                                    {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                </div>
                                <div class="text-sm font-semibold text-gray-900">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $student->classes->isNotEmpty() ? $student->classes->first()->name : 'Non assignée' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">{{ $student->section ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($student->status === 'active')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Actif</span>
                            @elseif($student->status === 'suspended')
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Suspendu</span>
                            @else
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('app.students.show', $student->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <p class="text-lg font-medium">Aucun élève ne correspond à ces critères.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $students->links() }}
        </div>
        @endif
    </div>
</div>
@endsection