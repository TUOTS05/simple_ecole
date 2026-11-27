@extends('layouts.app')

@section('title', 'Développement — '.$student->first_name)
@section('page_title', 'Suivi du développement')

@section('content')
@php
    $chips = 'px-3 py-2 rounded-lg border-2 border-gray-200 text-sm cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/10';
@endphp
<div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <div class="flex items-center justify-between text-sm">
        <a href="{{ route('app.creche.children.show', $student) }}" class="text-primary hover:underline">← Dossier crèche</a>
        <a href="{{ route('app.creche.journal.child', $student) }}" class="text-primary hover:underline">Journée →</a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <h1 class="text-xl font-bold text-gray-900">🌱 {{ $student->first_name }} {{ $student->last_name }}</h1>
        <p class="text-sm text-gray-500">Repères d'éveil, sans note ni classement : ce qui est observé, ce qui est en cours, ce qui est à stimuler.</p>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach($domains as $domain)
            @php $last = $latest->get($domain); @endphp
            <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                <span class="text-sm font-medium text-gray-700">{{ $domain }}</span>
                @if($last)
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $last->level_badge_class }}" title="{{ $last->observed_on->format('d/m/Y') }}">{{ $last->level_label }}</span>
                @else
                <span class="text-xs text-gray-400">Pas encore observé</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if($canWrite)
    <form action="{{ route('app.creche.development.store', $student) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        @csrf
        <h2 class="font-semibold text-gray-800">Nouvelle observation</h2>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Domaine *</label>
            <div class="flex flex-wrap gap-2">
                @foreach($activeDomains as $domain)
                <label class="{{ $chips }}"><input type="radio" name="domain" value="{{ $domain }}" class="sr-only" {{ old('domain') === $domain ? 'checked' : '' }}> {{ $domain }}</label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Niveau *</label>
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Models\DevelopmentObservation::LEVELS as $key => $label)
                <label class="{{ $chips }}"><input type="radio" name="level" value="{{ $key }}" class="sr-only" {{ old('level') === $key ? 'checked' : '' }}> {{ $label }}</label>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="observed_on" value="{{ old('observed_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ce qui a été observé</label>
                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="2000" placeholder="Ex : tient assis seul quelques secondes" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>
        <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold">Enregistrer</button>
    </form>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <h2 class="font-semibold text-gray-800 mb-3">Historique</h2>
        @forelse($observations as $observation)
        <div class="py-2 border-b border-gray-100 last:border-0 flex items-start justify-between gap-3">
            <div>
                <span class="text-sm text-gray-500">{{ $observation->observed_on->format('d/m/Y') }}</span>
                <span class="font-medium">{{ $observation->domain }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $observation->level_badge_class }}">{{ $observation->level_label }}</span>
                @if($observation->notes)<p class="text-sm text-gray-600">{{ $observation->notes }}</p>@endif
                <p class="text-xs text-gray-400">{{ $observation->staff?->full_name }}</p>
            </div>
            @if($canWrite && (auth()->user()->can('creche.manage') || ($observation->staff_id === auth()->id() && $observation->created_at->isToday())))
            <form action="{{ route('app.creche.development.destroy', $observation) }}" method="POST" onsubmit="return confirm('Retirer cette observation ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:underline">Retirer</button>
            </form>
            @endif
        </div>
        @empty
        <p class="text-sm text-gray-500">Aucune observation pour le moment.</p>
        @endforelse
    </div>
</div>
@endsection
