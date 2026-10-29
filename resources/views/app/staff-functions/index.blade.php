@extends('layouts.app')

@section('title', 'Fonctions administratives')
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

    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Fonctions administratives</h1>
            <p class="text-gray-600 mt-1">Définissez les fonctions de votre établissement et les modules auxquels chacune donne accès.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('app.staff.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-semibold transition">👤 Comptes du personnel</a>
            <a href="{{ route('app.staff-functions.create') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">+ Nouvelle fonction</a>
        </div>
    </div>

    @if($suggested)
    <form action="{{ route('app.staff-functions.suggested') }}" method="POST" class="bg-secondary/60 border border-yellow-200 rounded-lg p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
        @csrf
        <span class="text-sm text-gray-700">Fonctions types disponibles : <strong>{{ implode(', ', array_keys($suggested)) }}</strong></span>
        <button type="submit" class="text-sm bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg">Ajouter ces fonctions</button>
    </form>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($functions as $function)
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex justify-between items-start gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">{{ $function->name }}</h3>
                    @if($function->description)<p class="text-sm text-gray-500">{{ $function->description }}</p>@endif
                    <p class="text-sm text-gray-500 mt-1">{{ $function->members_count }} membre(s)</p>
                </div>
                <div class="flex space-x-3 text-sm whitespace-nowrap">
                    <a href="{{ route('app.staff-functions.edit', $function) }}" class="text-yellow-600 hover:text-yellow-800">✏️ Modifier</a>
                    <form action="{{ route('app.staff-functions.destroy', $function) }}" method="POST" onsubmit="return confirm('Supprimer cette fonction ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                    </form>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                @forelse($function->modules() as $module)
                    <span class="px-2 py-1 rounded-full text-xs bg-primary/15 text-gray-700">{{ $modules[$module]['icon'] }} {{ $modules[$module]['label'] }}</span>
                @empty
                    <span class="text-xs text-gray-400">Aucun module : seul le profil personnel est accessible.</span>
                @endforelse
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500 lg:col-span-2">Aucune fonction définie pour l'instant.</div>
        @endforelse
    </div>
@endsection
