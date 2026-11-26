@extends('layouts.app')

@section('title', 'Matières')
@section('page_title', 'Configuration des matières')

@section('content')

@if(session('success'))
<div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
    <ul class="list-disc list-inside text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Matières</h1>
        <p class="text-gray-600 mt-1">
            Configurez les matières par cycle et niveau{{ $currentYear ? ' — année '.$currentYear->name : '' }}.
            L'ordre et les domaines définis ici sont ceux du bulletin.
        </p>
    </div>
    <a href="{{ route('app.subjects.create') }}"
        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition whitespace-nowrap">
        + Configurer les matières
    </a>
</div>

<!-- Copier les matières -->
@if($copySources->isNotEmpty() && $currentYear)
<details class="bg-white rounded-lg shadow p-6 mb-6" @if($errors->has('copy') || $errors->has('source') || $errors->has('target_levels')) open @endif>
    <summary class="cursor-pointer font-semibold text-gray-800">📋 Copier les matières d'un niveau vers d'autres niveaux (ou d'une année à l'autre)</summary>
    <form method="POST" action="{{ route('app.subjects.copy') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Copier depuis</label>
            <select name="source" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                <option value="">-- Choisir --</option>
                @foreach($copySources as $source)
                <option value="{{ $source->school_year_id }}|{{ $source->level }}" @selected(old('source') === $source->school_year_id.'|'.$source->level)>
                    {{ $source->level }} — {{ $source->year_name }} ({{ $source->total }} matières)
                </option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Vers le(s) niveau(x) de l'année {{ $currentYear->name }}</label>
            <div class="flex flex-wrap gap-3">
                @foreach($levelsByCycle as $cycle => $levels)
                    @foreach($levels as $level)
                    <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" name="target_levels[]" value="{{ $level }}" @checked(in_array($level, old('target_levels', []), true)) class="form-checkbox rounded text-primary">
                        {{ $level }}
                    </label>
                    @endforeach
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-2">Coefficients, barèmes, domaines et ordre sont repris. Les matières déjà présentes sur un niveau ne sont pas dupliquées.</p>
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-lg font-semibold transition">Copier</button>
        </div>
    </form>
</details>
@endif

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
                @foreach(['TPS', 'PS', 'MS', 'GS', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'] as $level)
                <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                @endforeach
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

<!-- Tableau (modifiable) -->
<datalist id="subject-domains">
    @foreach($domains as $domain)<option value="{{ $domain }}">@endforeach
</datalist>

<form method="POST" action="{{ route('app.subjects.bulk-update') }}" id="subjects-bulk-form">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Cycle</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Niveau</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Ordre</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Matière</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Domaine</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Coefficient</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Notée sur</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2 px-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap
                                    {{ $subject->cycle === 'maternelle' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $subject->cycle === 'maternelle' ? '🧒 Maternelle' : '📚 Primaire' }}
                        </span>
                    </td>
                    <td class="py-2 px-4 font-semibold">{{ $subject->level }}</td>
                    <td class="py-2 px-4">
                        <input type="number" name="subjects[{{ $subject->id }}][sort_order]" value="{{ $subject->sort_order }}" min="0" max="9999"
                               class="w-20 px-2 py-1 border border-gray-300 rounded">
                    </td>
                    <td class="py-2 px-4">{{ $subject->name }}</td>
                    <td class="py-2 px-4">
                        <input type="text" name="subjects[{{ $subject->id }}][domain]" value="{{ $subject->domain }}" list="subject-domains" maxlength="100" placeholder="—"
                               class="w-40 px-2 py-1 border border-gray-300 rounded">
                    </td>
                    <td class="py-2 px-4">
                        <input type="number" name="subjects[{{ $subject->id }}][coefficient]" value="{{ $subject->coefficient + 0 }}" min="1" max="10" step="0.5"
                               class="w-20 px-2 py-1 border border-gray-300 rounded">
                    </td>
                    <td class="py-2 px-4">
                        <select name="subjects[{{ $subject->id }}][max_score]" class="px-2 py-1 border border-gray-300 rounded">
                            @foreach([10, 20, 40, 50, 100] as $max)
                            <option value="{{ $max }}" @selected((int) $subject->max_score === $max)>/{{ $max }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="py-2 px-4">
                        <button type="submit" form="delete-subject-{{ $subject->id }}" class="text-red-600 hover:text-red-800"
                                onclick="return confirm('Supprimer cette matière ?')">🗑️</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-8 text-center text-gray-500">
                        Aucune matière configurée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subjects->isNotEmpty())
    <div class="mt-4 flex justify-end">
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            Enregistrer l'ordre et les domaines
        </button>
    </div>
    @endif
</form>

{{-- Formulaires de suppression hors du formulaire principal (pas de formulaires imbriqués) --}}
@foreach($subjects as $subject)
<form id="delete-subject-{{ $subject->id }}" action="{{ route('app.subjects.destroy', $subject) }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach

@endsection
