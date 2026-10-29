@extends('layouts.app')

@section('title', $function->exists ? 'Modifier la fonction' : 'Nouvelle fonction')
@section('page_title', 'Personnel')

@php $granted = old('permissions', $function->permissions ?? []); @endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('app.staff-functions.index') }}" class="text-sm text-gray-500 hover:text-primary">← Fonctions administratives</a>
    <h1 class="text-3xl font-bold text-gray-800 mb-6">{{ $function->exists ? 'Modifier « '.$function->name.' »' : 'Nouvelle fonction' }}</h1>

    @if($errors->any())
        <div class="bg-danger text-white px-6 py-4 rounded-lg mb-6">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ $function->exists ? route('app.staff-functions.update', $function) : route('app.staff-functions.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf
        @if($function->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé de la fonction *</label>
                <input type="text" name="name" value="{{ old('name', $function->name) }}" required placeholder="Censeur, Économe, Surveillant général…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $function->description) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-800 mb-1">Modules accessibles</h3>
            <p class="text-sm text-gray-500 mb-3">Les paramètres de l'école, les documents, le règlement et la gestion du personnel restent réservés à l'administrateur.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($modules as $key => $module)
                <label class="flex items-start p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $granted, true) ? 'checked' : '' }} class="w-5 h-5 mt-0.5 text-primary rounded focus:ring-primary">
                    <span class="ml-3">
                        <span class="font-medium text-gray-800">{{ $module['icon'] }} {{ $module['label'] }}</span>
                        <span class="block text-xs text-gray-500">{{ $module['description'] }}</span>
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
