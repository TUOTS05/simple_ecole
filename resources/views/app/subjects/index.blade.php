@extends('layouts.app')

@section('title', 'Matières')
@section('page_title', 'Configuration des matières')

@section('content')

@if(session('success'))
<div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
    {{ session('success') }}
</div>
@endif

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Matières</h1>
        <p class="text-gray-600 mt-1">Configurez les matières par cycle et niveau</p>
    </div>
    <a href="{{ route('app.subjects.create') }}"
        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
        + Configurer les matières
    </a>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('app.subjects.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cycle</label>
            <select name="cycle" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">Tous les cycles</option>
                <option value="maternelle" {{ request('cycle') === 'maternelle' ? 'selected' : '' }}>🧒 Maternelle</option>
                <option value="primaire" {{ request('cycle') === 'primaire' ? 'selected' : '' }}>📚 Primaire</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
            <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">Tous les niveaux</option>
                <option value="TPS" {{ request('level') === 'TPS' ? 'selected' : '' }}>TPS</option>
                <option value="PS" {{ request('level') === 'PS' ? 'selected' : '' }}>PS</option>
                <option value="MS" {{ request('level') === 'MS' ? 'selected' : '' }}>MS</option>
                <option value="GS" {{ request('level') === 'GS' ? 'selected' : '' }}>GS</option>
                <option value="CP" {{ request('level') === 'CP' ? 'selected' : '' }}>CP</option>
                <option value="CP1" {{ request('level') === 'CP1' ? 'selected' : '' }}>CP1</option>
                <option value="CP2" {{ request('level') === 'CP2' ? 'selected' : '' }}>CP2</option>
                <option value="CE1" {{ request('level') === 'CE1' ? 'selected' : '' }}>CE1</option>
                <option value="CE2" {{ request('level') === 'CE2' ? 'selected' : '' }}>CE2</option>
                <option value="CM1" {{ request('level') === 'CM1' ? 'selected' : '' }}>CM1</option>
                <option value="CM2" {{ request('level') === 'CM2' ? 'selected' : '' }}>CM2</option>
            </select>
        </div>

        <div class="flex items-end space-x-2">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">
                Filtrer
            </button>
            <a href="{{ route('app.subjects.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-semibold transition">
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
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Cycle</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Niveau</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matière</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Coefficient</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Notée sur</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $subject)
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $subject->cycle === 'maternelle' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $subject->cycle === 'maternelle' ? '🧒 Maternelle' : '📚 Primaire' }}
                    </span>
                </td>
                <td class="py-3 px-4 font-semibold">{{ $subject->level }}</td>
                <td class="py-3 px-4">{{ $subject->name }}</td>
                <td class="py-3 px-4 text-center">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                        {{ $subject->coefficient }}
                    </span>
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        {{ $subject->max_score }}
                    </span>
                </td>
                <td class="py-3 px-4">
                    <form action="{{ route('app.subjects.destroy', $subject) }}" method="POST" class="inline"
                        onsubmit="return confirm('Êtes-vous sûr ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">
                            🗑️
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-8 text-center text-gray-500">
                    Aucune matière configurée
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection