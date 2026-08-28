@extends('layouts.app')

@section('title', $label)
@section('page_title', $label)

@section('content')
    @if(session('success'))
        <div class="bg-accent text-white px-6 py-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ $label }}</h1>
            <p class="text-gray-600 mt-1">{{ $description }}</p>
        </div>
        <a href="{{ route('app.extra-staff.create', $type) }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouveau compte
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Nom complet</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Email</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Téléphone</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Genre</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">{{ $member->last_name }} {{ $member->first_name }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $member->email }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $member->phone ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $member->gender === 'M' ? 'bg-primary text-white' : 'bg-pink-300 text-white' }}">
                                {{ $member->gender === 'M' ? 'Homme' : 'Femme' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.extra-staff.edit', [$type, $member]) }}" class="text-yellow-600 hover:text-yellow-800">✏️ Modifier</a>
                                <form action="{{ route('app.extra-staff.destroy', [$type, $member]) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Aucun compte pour le moment</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($staff->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $staff->links() }}</div>
        @endif
    </div>
@endsection
