@extends('layouts.app')

@section('title', 'Paramètres de la crèche')
@section('page_title', 'Paramètres de la crèche')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('app.creche.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">👶 Groupes d'âge</h2>
            <p class="text-sm text-gray-600">Niveaux proposés à la création d'un groupe de crèche (Paramètres › Classes), un par ligne.</p>
            <textarea name="levels" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono text-sm">{{ $levels }}</textarea>
            @if($levelsInUse)
            <p class="text-xs text-gray-500">
                Niveaux portés par vos groupes actuels : <strong>{{ implode(', ', $levelsInUse) }}</strong>.
                Retirer un niveau de la liste ne modifie pas ces groupes.
            </p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">🕖 Horaires</h2>
            <p class="text-sm text-gray-600">Horaires par défaut de la crèche. Un groupe peut avoir ses propres horaires (fiche du groupe). L'heure de fermeture sert à repérer les récupérations tardives.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ouverture *</label>
                    <input type="time" name="opening_time" value="{{ $openingTime }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fermeture *</label>
                    <input type="time" name="closing_time" value="{{ $closingTime }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-2">
            <h2 class="text-lg font-semibold text-gray-800">👥 Capacité des groupes</h2>
            <label class="flex items-start gap-3">
                <input type="hidden" name="enforce_capacity" value="0">
                <input type="checkbox" name="enforce_capacity" value="1" {{ $enforceCapacity ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                <span class="text-sm text-gray-700">
                    Refuser l'inscription d'un enfant dans un groupe complet
                    <span class="block text-xs text-gray-500">La capacité se règle sur chaque groupe. Sans cette option, elle reste indicative.</span>
                </span>
            </label>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">📒 Journal quotidien</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégories d'activités (une par ligne)</label>
                    <textarea name="activity_categories" rows="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">{{ $activityCategories }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Soins d'hygiène (un par ligne)</label>
                    <textarea name="hygiene_types" rows="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">{{ $hygieneTypes }}</textarea>
                </div>
            </div>
            <p class="text-sm text-gray-600">Repères de la liste « À faire » des auxiliaires :</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repas à renseigner après</label>
                    <input type="time" name="meal_time" value="{{ $mealTime }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Goûter à renseigner après</label>
                    <input type="time" name="snack_time" value="{{ $snackTime }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Change à prévoir toutes les (heures)</label>
                    <input type="number" name="diaper_interval_hours" value="{{ $diaperInterval }}" min="1" max="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">⚠️ Incidents et 🌱 développement</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Domaines du suivi du développement (un par ligne)</label>
                    <textarea name="development_domains" rows="7" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">{{ $developmentDomains }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incidents visibles par les parents</label>
                    <select name="incidents_parent_visibility" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="validated" {{ $incidentsVisibility === 'validated' ? 'selected' : '' }}>Oui, une fois validés par la responsable</option>
                        <option value="none" {{ $incidentsVisibility === 'none' ? 'selected' : '' }}>Non, communication directe par la direction</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">S'appliquera à l'espace parent.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 space-y-3">
            <h2 class="text-lg font-semibold text-gray-800">📱 Notifications SMS aux parents</h2>
            <input type="hidden" name="notifications_form" value="1">
            @unless($smsEnabled)
            <p class="text-sm bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-3 py-2">
                Les SMS sont désactivés pour votre école : aucune notification ne partira tant qu'ils ne sont pas activés dans
                <a href="{{ route('app.settings.sms') }}" class="underline font-semibold">Configuration SMS</a>.
            </p>
            @endunless
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($notifyEvents as $event => $config)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="notify_{{ $event }}" value="1" {{ $config['checked'] ? 'checked' : '' }} class="rounded border-gray-300 text-primary focus:ring-primary">
                    {{ $config['label'] }}
                </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500">Chaque SMS est facturé par l'opérateur. Les messages ne mentionnent que le prénom de l'enfant.</p>
        </div>

        <div>
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
