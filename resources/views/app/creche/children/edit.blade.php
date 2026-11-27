@extends('layouts.app')

@section('title', 'Dossier crèche — '.$student->first_name.' '.$student->last_name)
@section('page_title', 'Modifier le dossier crèche')

@section('content')
@php
    $input = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent';
    $longFields = ['introduced_foods', 'forbidden_foods', 'allergies', 'food_preferences', 'sleep_ritual', 'sleep_notes', 'changing_habits', 'specific_products', 'hygiene_notes'];
    $placeholders = [
        'milk_type' => 'Ex : lait 2e âge, lait maternel tiré',
        'usual_quantity' => 'Ex : 150 ml',
        'feeding_frequency' => 'Ex : toutes les 3 heures',
        'usual_sleep_time' => 'Ex : 10h et 13h30',
        'usual_sleep_duration' => 'Ex : 1h30',
        'comforter' => 'Ex : lapin bleu',
        'cream' => 'Ex : crème pour le change fournie par les parents',
    ];
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <a href="{{ route('app.creche.children.show', $student) }}" class="text-primary hover:underline text-sm">← Retour au dossier de {{ $student->first_name }}</a>

    @if($errors->any())
    <div class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('app.creche.children.update', $student) }}" method="POST" class="mt-4 space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">📋 Informations générales — {{ $student->first_name }} {{ $student->last_name }}</h2>
            <p class="text-xs text-gray-500">Identité, adresse et famille se modifient sur la fiche élève (commune à toute la scolarité).</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entrée en crèche</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', $profile->entry_date?->format('Y-m-d')) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Régime de garde</label>
                    <select name="care_arrangement" class="{{ $input }} bg-white">
                        <option value="">—</option>
                        @foreach(\App\Models\ChildCareProfile::CARE_ARRANGEMENTS as $key => $label)
                        <option value="{{ $key }}" {{ old('care_arrangement', $profile->care_arrangement) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Personne référente</label>
                    <select name="referent_user_id" class="{{ $input }} bg-white">
                        <option value="">—</option>
                        @foreach($referents as $person)
                        <option value="{{ $person->id }}" {{ (string) old('referent_user_id', $profile->referent_user_id) === (string) $person->id ? 'selected' : '' }}>{{ $person->full_name }} ({{ $person->role_label }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @foreach(['feeding' => '🍼 Alimentation', 'sleep' => '😴 Sommeil', 'hygiene' => '🧼 Hygiène'] as $section => $title)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">{{ $title }}</h2>

            @if($section === 'feeding')
            <div class="flex flex-wrap gap-6 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="breastfed" value="1" {{ old('breastfed', $profile->breastfed) ? 'checked' : '' }} class="rounded border-gray-300 text-primary"> Allaitement</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="formula_fed" value="1" {{ old('formula_fed', $profile->formula_fed) ? 'checked' : '' }} class="rounded border-gray-300 text-primary"> Lait artificiel</label>
            </div>
            @elseif($section === 'sleep')
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="pacifier" value="1" {{ old('pacifier', $profile->pacifier) ? 'checked' : '' }} class="rounded border-gray-300 text-primary"> Tétine</label>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach(\App\Models\ChildCareProfile::SECTIONS[$section] as $field => $label)
                <div class="{{ in_array($field, $longFields, true) ? 'sm:col-span-2' : '' }}">
                    <label class="block text-sm font-medium {{ in_array($field, ['allergies', 'forbidden_foods'], true) ? 'text-red-700' : 'text-gray-700' }} mb-1">{{ $label }}</label>
                    @if(in_array($field, $longFields, true))
                    <textarea name="{{ $field }}" rows="2" maxlength="2000" class="{{ $input }}">{{ old($field, $profile->{$field}) }}</textarea>
                    @else
                    <input type="text" name="{{ $field }}" value="{{ old($field, $profile->{$field}) }}" maxlength="150" placeholder="{{ $placeholders[$field] ?? '' }}" class="{{ $input }}">
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-2">
            <h2 class="text-lg font-semibold text-gray-800">💬 Particularités</h2>
            <p class="text-xs text-gray-500">Habitudes importantes communiquées par les parents.</p>
            <textarea name="particularities" rows="4" maxlength="2000" class="{{ $input }}">{{ old('particularities', $profile->particularities) }}</textarea>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Enregistrer le dossier</button>
            <a href="{{ route('app.creche.children.show', $student) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
