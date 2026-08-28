@extends('layouts.app')

@section('title', 'Catalogue des Extras')
@section('page_title', 'Catalogue des Extras')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">Toutes</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->icon }} {{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <a href="{{ route('extras.catalogue.create') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition">+ Nouvel Extra</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($extras as $extra)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase">{{ $extra->category->icon }} {{ $extra->category->name }}</span>
                    <h3 class="font-bold text-gray-800">{{ $extra->name }}</h3>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-bold {{ $extra->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $extra->status === 'active' ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $extra->description ?: 'Aucune description.' }}</p>
            <div class="flex flex-wrap gap-2 text-xs text-gray-500 mb-4">
                <span class="bg-gray-100 px-2 py-1 rounded">Code: {{ $extra->code }}</span>
                <span class="bg-gray-100 px-2 py-1 rounded">{{ $extra->billing_type === 'recurring' ? 'Périodique' : 'Frais unique' }}</span>
                @if($extra->capacity)
                <span class="bg-gray-100 px-2 py-1 rounded">{{ $extra->occupiedSeatsCount() }}/{{ $extra->capacity }} places</span>
                @endif
            </div>
            <a href="{{ route('extras.catalogue.edit', $extra->id) }}" class="block text-center bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg font-semibold text-sm transition">Gérer</a>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500 bg-white rounded-lg border border-dashed border-gray-300">
            Aucun extra créé pour le moment. <a href="{{ route('extras.catalogue.create') }}" class="text-primary underline">En créer un</a>.
        </div>
        @endforelse
    </div>
</div>
@endsection
