@extends('layouts.app')

@section('title', 'Fiche Parent')
@section('page_title', 'Profil du Parent')

@section('content')
<div class="max-w-5xl mx-auto">

    <!-- En-tête -->
    <div class="mb-6 flex items-center">
        <a href="{{ route('app.parents.index') }}" class="mr-4 p-2 rounded-full hover:bg-gray-200 transition">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ strtoupper($parent->last_name) }} {{ $parent->first_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">Compte parent</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Colonne Gauche : Coordonnées -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="w-24 h-24 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-4 text-2xl font-bold">
                    {{ strtoupper(substr($parent->first_name, 0, 1)) }}{{ strtoupper(substr($parent->last_name, 0, 1)) }}
                </div>
                <h2 class="text-lg font-bold text-gray-900">{{ $parent->first_name }} {{ $parent->last_name }}</h2>
                <p class="text-sm text-gray-500 mb-4">{{ $parent->children->count() }} enfant{{ $parent->children->count() > 1 ? 's' : '' }} lié{{ $parent->children->count() > 1 ? 's' : '' }}</p>

                <div class="text-left space-y-3 mt-6 border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Email</p>
                        <p class="text-sm text-gray-800">{{ $parent->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Téléphone</p>
                        <p class="text-sm text-gray-800">{{ $parent->phone ?? 'Non renseigné' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Enfants liés -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Enfants liés à ce compte</h3>

                @forelse($parent->children as $child)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-center">
                        <div class="h-10 w-10 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-bold mr-3">
                            {{ strtoupper(substr($child->first_name, 0, 1)) }}{{ strtoupper(substr($child->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $child->first_name }} {{ strtoupper($child->last_name) }}</p>
                            <p class="text-xs text-gray-500">
                                Matricule {{ $child->matricule ?? 'N/A' }}
                                @if($child->classes->isNotEmpty())
                                    · {{ $child->classes->first()->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('app.students.show', $child->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                        Voir la fiche élève →
                    </a>
                </div>
                @empty
                <p class="text-sm text-gray-500 italic">Aucun enfant n'est encore lié à ce compte parent.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
