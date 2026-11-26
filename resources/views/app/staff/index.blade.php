@extends('layouts.app')

@section('title', 'Personnel')
@section('page_title', 'Personnel')

@section('content')
    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Personnel</h1>
            <p class="text-gray-600 mt-1">
                Enseignants, secrétaires, comptables, responsables cantine et transport : un seul écran, avec le rôle et les droits de chacun.
            </p>
            @if($school?->max_users)
                <p class="text-sm text-gray-500 mt-1">Comptes utilisés : {{ $school->activeUserCount() }} / {{ $school->max_users }} (forfait)</p>
            @endif
        </div>
        <a href="{{ route('app.staff.create', request('role') ? ['role' => request('role')] : []) }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition whitespace-nowrap">
            + Nouveau membre
        </a>
    </div>

    {{-- Filtres par rôle --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('app.staff.index', request()->only('search')) }}"
           class="px-4 py-2 rounded-full text-sm font-medium {{ request('role') ? 'bg-white text-gray-700 border border-gray-200' : 'bg-primary text-white' }}">
            Tous ({{ $countsByRole->sum() }})
        </a>
        @foreach($roles as $value => $label)
            <a href="{{ route('app.staff.index', array_merge(request()->only('search'), ['role' => $value])) }}"
               class="px-4 py-2 rounded-full text-sm font-medium {{ request('role') === $value ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
                {{ $label }} ({{ $countsByRole[$value] ?? 0 }})
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('app.staff.index') }}" class="mb-4 flex gap-2">
        @if(request('role'))<input type="hidden" name="role" value="{{ request('role') }}">@endif
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou email…" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg">Rechercher</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom complet</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Rôle</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Droits</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">{{ $member->last_name }} {{ $member->first_name }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary whitespace-nowrap">{{ $member->role_label }}</span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap">
                            @if($member->hasCustomPermissions())
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">Personnalisés</span>
                            @else
                                <span class="text-gray-500">Par défaut</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-1">({{ count($member->effectivePermissions()) }})</span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $member->email }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $member->phone ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-3 whitespace-nowrap">
                                <a href="{{ route('app.staff.edit', $member) }}" class="text-yellow-600 hover:text-yellow-800">✏️ Modifier</a>
                                <form action="{{ route('app.staff.destroy', $member) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce compte ? La personne ne pourra plus se connecter.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Aucun membre du personnel trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($staff->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $staff->links() }}</div>
        @endif
    </div>
@endsection
