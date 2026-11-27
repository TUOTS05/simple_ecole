@extends('layouts.app')

@section('title', 'Enfants de la crèche')
@section('page_title', 'Enfants de la crèche')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Groupe</label>
                <select name="group" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Tous mes groupes</option>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ $groupId === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, prénom ou matricule"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-5 py-2 rounded-lg font-semibold transition">Rechercher</button>
        </form>
    </div>

    @if($groups->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center text-gray-500">
        Vous n'êtes affecté(e) à aucun groupe de crèche. Demandez à la direction de vous affecter à un groupe.
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Enfant</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 hidden sm:table-cell">Groupe</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 hidden md:table-cell">Âge</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600">Vigilance</th>
                    <th class="text-center py-3 px-4 font-semibold text-gray-600 hidden sm:table-cell">Personnes autorisées</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($children as $child)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <a href="{{ route('app.creche.children.show', $child) }}" class="font-semibold text-primary hover:underline">
                            {{ $child->last_name }} {{ $child->first_name }}
                        </a>
                        <div class="text-xs text-gray-500">{{ $child->matricule }}</div>
                    </td>
                    <td class="py-3 px-4 hidden sm:table-cell">{{ $child->crecheClass()?->name }}</td>
                    <td class="py-3 px-4 hidden md:table-cell">{{ $child->birth_date ? (int) $child->birth_date->diffInMonths(now()).' mois' : '—' }}</td>
                    <td class="py-3 px-4">
                        @if($child->careProfile?->hasAlerts())
                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 font-semibold">⚠ Allergie / interdit</span>
                        @elseif(! $child->careProfile)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">Dossier à compléter</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center hidden sm:table-cell">
                        @if($child->authorized_count)
                        {{ $child->authorized_count }}
                        @else
                        <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">Aucune</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-500">Aucun enfant trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $children->links() }}
    @endif
</div>
@endsection
