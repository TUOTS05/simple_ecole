@extends('layouts.app')

@php
    $isEdit = $incident->exists;
    $title = $isEdit ? 'Modifier l\'incident' : 'Déclarer un incident';
    $input = 'w-full px-3 py-3 border border-gray-300 rounded-lg';
    $chips = 'px-3 py-2 rounded-lg border-2 border-gray-200 text-sm cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50';
@endphp

@section('title', $title)
@section('page_title', $title)

@section('content')
<div class="max-w-2xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <a href="{{ $isEdit ? route('app.creche.incidents.show', $incident) : route('app.creche.incidents.index') }}" class="text-primary hover:underline text-sm">← Retour</a>

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form action="{{ $isEdit ? route('app.creche.incidents.update', $incident) : route('app.creche.incidents.store') }}" method="POST"
          class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-4">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <h1 class="text-xl font-bold text-gray-900">⚠ {{ $title }}</h1>

        @if($isEdit)
        <p class="font-semibold">{{ $children->first()->first_name }} {{ $children->first()->last_name }}</p>
        @else
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Enfant concerné *</label>
            <select name="student_id" required class="{{ $input }} bg-white">
                <option value="">— Choisir —</option>
                @foreach($children as $child)
                <option value="{{ $child->id }}" {{ (int) old('student_id', $selectedStudent) === $child->id ? 'selected' : '' }}>{{ $child->first_name }} {{ $child->last_name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                <input type="date" name="occurred_on" value="{{ old('occurred_on', $incident->occurred_on?->toDateString()) }}" max="{{ today()->toDateString() }}" required class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heure *</label>
                <input type="time" name="occurred_at" value="{{ old('occurred_at', $incident->timeLabel()) }}" required class="{{ $input }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Models\CareIncident::TYPES as $key => $label)
                <label class="{{ $chips }}">
                    <input type="radio" name="type" value="{{ $key }}" class="sr-only" {{ old('type', $incident->type) === $key ? 'checked' : '' }}> {{ $label }}
                </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ce qui s'est passé *</label>
            <textarea name="description" rows="4" maxlength="3000" required class="{{ $input }}">{{ old('description', $incident->description) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Décrivez les faits observés, sans diagnostic médical.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mesures prises</label>
            <textarea name="action_taken" rows="3" maxlength="3000" placeholder="Ex : froid appliqué, enfant consolé, surveillance" class="{{ $input }}">{{ old('action_taken', $incident->action_taken) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Personne prévenue</label>
                <input type="text" name="person_informed" value="{{ old('person_informed', $incident->person_informed) }}" maxlength="150" placeholder="Ex : la mère, par téléphone" class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prévenue le</label>
                <input type="datetime-local" name="informed_at" value="{{ old('informed_at', $incident->informed_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
            </div>
        </div>

        <button type="submit" class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg text-lg font-bold">
            {{ $isEdit ? 'Enregistrer les modifications' : 'Déclarer l\'incident' }}
        </button>
    </form>
</div>
@endsection
