@extends('layouts.app')

@section('title', 'Annuaire des Parents')
@section('page_title', 'Parents')

@section('content')
<div class="max-w-7xl mx-auto">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Annuaire des Parents</h1>
            <p class="text-gray-600 mt-1">Retrouvez chaque parent et les enfants qui lui sont liés.</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <form method="GET" action="{{ route('app.parents.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
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
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email ou téléphone du parent" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 text-sm font-semibold rounded-lg hover:bg-primary-dark transition flex items-center justify-center">
                    Filtrer
                </button>
                <a href="{{ route('app.parents.index') }}" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 text-sm font-semibold rounded-lg hover:bg-gray-300 transition flex items-center justify-center">
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Parent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enfant(s)</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($parents as $parentUser)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold mr-3">
                                    {{ strtoupper(substr($parentUser->first_name, 0, 1)) }}{{ strtoupper(substr($parentUser->last_name, 0, 1)) }}
                                </div>
                                <div class="text-sm font-semibold text-gray-900">{{ strtoupper($parentUser->last_name) }} {{ $parentUser->first_name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div>{{ $parentUser->email }}</div>
                            <div class="text-xs text-gray-400">{{ $parentUser->phone ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @forelse($parentUser->children as $child)
                            <span class="inline-block bg-blue-50 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full mr-1 mb-1">
                                {{ $child->first_name }} {{ strtoupper($child->last_name) }}
                                @if($child->classes->isNotEmpty())
                                    · {{ $child->classes->first()->name }}
                                @endif
                            </span>
                            @empty
                            <span class="text-xs text-gray-400 italic">Aucun enfant lié</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('app.parents.show', $parentUser->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir la fiche">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <p class="text-lg font-medium">Aucun parent ne correspond à ces critères.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parents->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $parents->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
