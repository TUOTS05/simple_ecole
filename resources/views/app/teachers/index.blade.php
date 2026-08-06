@extends('layouts.app')

@section('title', 'Gestion des Enseignants')
@section('page_title', 'Enseignants')

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
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Enseignants</h1>
            <p class="text-gray-600 mt-1">Gérez le personnel enseignant de votre école</p>
        </div>
        <a href="{{ route('app.teachers.create') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
            + Nouvel Enseignant
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
                @forelse($teachers as $teacher)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 font-semibold">{{ $teacher->last_name }} {{ $teacher->first_name }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $teacher->email }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $teacher->phone ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $teacher->gender === 'M' ? 'bg-primary text-white' : 'bg-pink-300 text-white' }}">
                                {{ $teacher->gender === 'M' ? 'Homme' : 'Femme' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('app.teachers.edit', $teacher) }}" class="text-yellow-600 hover:text-yellow-800">✏️ Modifier</a>
                                <form action="{{ route('app.teachers.destroy', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Aucun enseignant trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($teachers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">{{ $teachers->links() }}</div>
        @endif
    </div>
@endsection