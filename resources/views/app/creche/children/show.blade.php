@extends('layouts.app')

@section('title', 'Dossier crèche — '.$student->first_name.' '.$student->last_name)
@section('page_title', 'Dossier crèche')

@section('content')
@php
    $group = $student->crecheClass();
    $months = $student->birth_date ? (int) $student->birth_date->diffInMonths(now()) : null;
    $show = fn ($value) => filled($value) ? e($value) : '<span class="text-gray-400">—</span>';
    $openForm = old('_form');
@endphp
<style>[x-cloak] { display: none !important; }</style>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

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

    <!-- En-tête : identité -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 flex flex-col sm:flex-row gap-6">
        <div class="w-28 h-28 shrink-0 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center text-5xl">
            @if($student->photo)
            <img src="{{ route('app.students.photo', $student) }}" alt="Photo de {{ $student->first_name }}" class="w-full h-full object-cover">
            @else
            👶
            @endif
        </div>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-900">{{ $student->first_name }} {{ strtoupper($student->last_name) }}</h1>
            <p class="text-sm text-gray-500">Matricule {{ $student->matricule ?: '—' }}</p>
            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                @if($group)<span class="px-3 py-1 rounded-full bg-pink-100 text-pink-800">🍼 {{ $group->name }}</span>@endif
                @if($months !== null)<span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700">{{ $months }} mois · né(e) le {{ $student->birth_date->format('d/m/Y') }}</span>@endif
                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700">{{ $student->gender === 'M' ? 'Garçon' : 'Fille' }}</span>
            </div>
        </div>
        <div class="flex sm:flex-col gap-2">
            @if($canEdit)
            <a href="{{ route('app.creche.children.edit', $student) }}" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg text-center">Modifier le dossier</a>
            @endif
            @can('students.manage')
            <a href="{{ route('app.students.show', $student) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg text-center hover:bg-gray-50">Fiche élève</a>
            @endcan
        </div>
    </div>

    @if($profile->hasAlerts())
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg">
        <p class="font-bold">⚠ Points de vigilance</p>
        @if(filled($profile->allergies))<p class="mt-1"><strong>Allergies :</strong> {{ $profile->allergies }}</p>@endif
        @if(filled($profile->forbidden_foods))<p class="mt-1"><strong>Aliments interdits :</strong> {{ $profile->forbidden_foods }}</p>@endif
    </div>
    @endif

    @unless($profile->exists)
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-lg px-4 py-3">
        Le profil de prise en charge n'est pas encore renseigné.
        @if($canEdit)<a href="{{ route('app.creche.children.edit', $student) }}" class="underline font-semibold">Le compléter</a>@endif
    </div>
    @endunless

    <!-- Personnes autorisées à récupérer l'enfant -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6" x-data="{ adding: {{ $openForm === 'new' || ($canEdit && $pickups->isEmpty()) ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">🚸 Personnes autorisées à récupérer l'enfant</h2>
            @if($canEdit)
            <button type="button" @click="adding = !adding" class="text-sm text-primary font-semibold hover:underline" x-text="adding ? 'Fermer' : '+ Ajouter une personne'"></button>
            @endif
        </div>

        @if($canEdit)
        <datalist id="pickup-relationships">
            @foreach($relationships as $relationship)<option value="{{ $relationship }}">@endforeach
        </datalist>
        <div x-show="adding" x-cloak class="mb-6 border border-gray-200 rounded-lg p-4 bg-gray-50">
            @include('app.creche.children._pickup_form', [
                'pickup' => new \App\Models\AuthorizedPickup(['status' => 'authorized']),
                'action' => route('app.creche.pickups.store', $student),
                'method' => 'POST',
                'formId' => 'new',
            ])
        </div>
        @endif

        @if($pickups->isEmpty())
        <p class="text-sm text-red-600">Aucune personne enregistrée : le départ de l'enfant ne pourra pas être sécurisé.</p>
        @else
        <ul class="divide-y divide-gray-100">
            @foreach($pickups as $pickup)
            @php
                $state = $pickup->stateLabel();
                $badge = match ($state) {
                    'Autorisée' => 'bg-green-100 text-green-800',
                    'Interdite' => 'bg-red-600 text-white',
                    default => 'bg-gray-200 text-gray-700',
                };
            @endphp
            <li class="py-4" x-data="{ editing: {{ $openForm === 'pickup-'.$pickup->id ? 'true' : 'false' }} }">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 shrink-0 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-2xl">
                        @if($pickup->photo)
                        <img src="{{ route('app.creche.pickups.photo', $pickup) }}" alt="Photo de {{ $pickup->full_name }}" class="w-full h-full object-cover">
                        @else
                        👤
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-900">{{ $pickup->full_name }}</span>
                            <span class="text-sm text-gray-500">{{ $pickup->relationship }}</span>
                            <span class="px-2 py-0.5 text-xs rounded-full font-semibold {{ $badge }}">{{ $state === 'Interdite' ? 'NON AUTORISÉE' : $state }}</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            @if($pickup->phone)📞 {{ $pickup->phone }}@endif
                            @if($pickup->starts_on || $pickup->ends_on)
                            · {{ $pickup->starts_on ? 'du '.$pickup->starts_on->format('d/m/Y') : '' }} {{ $pickup->ends_on ? 'au '.$pickup->ends_on->format('d/m/Y') : '' }}
                            @endif
                        </p>
                        @if($pickup->comment)<p class="text-sm text-gray-500 italic mt-1">{{ $pickup->comment }}</p>@endif
                    </div>
                    @if($canEdit)
                    <div class="flex flex-col items-end gap-1 text-sm">
                        <button type="button" @click="editing = !editing" class="text-primary hover:underline">Modifier</button>
                        <form action="{{ route('app.creche.pickups.destroy', $pickup) }}" method="POST" onsubmit="return confirm(@js('Retirer '.$pickup->full_name.' de la liste ?'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Retirer</button>
                        </form>
                    </div>
                    @endif
                </div>
                @if($canEdit)
                <div x-show="editing" x-cloak class="mt-4 border border-gray-200 rounded-lg p-4 bg-gray-50">
                    @include('app.creche.children._pickup_form', [
                        'action' => route('app.creche.pickups.update', $pickup),
                        'method' => 'PUT',
                        'formId' => 'pickup-'.$pickup->id,
                    ])
                </div>
                @endif
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    <!-- Profil de prise en charge -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">📋 Informations générales</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Entrée en crèche</dt><dd>{!! $show($profile->entry_date?->format('d/m/Y')) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Régime de garde</dt><dd>{!! $show($profile->care_arrangement_label) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Personne référente</dt><dd>{!! $show($profile->referent?->full_name) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Adresse</dt><dd class="text-right">{!! $show($student->current_address) !!}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">👪 Famille</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Père</dt><dd class="text-right">{!! $show(trim($student->father_name.' '.$student->father_phone)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Mère</dt><dd class="text-right">{!! $show(trim($student->mother_name.' '.$student->mother_phone)) !!}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Tuteur / responsable</dt><dd class="text-right">{!! $show(trim($student->guardian_name.' '.($student->guardian_relation ? '('.$student->guardian_relation.')' : '').' '.$student->guardian_phone)) !!}</dd></div>
            </dl>
        </div>

        @foreach(['feeding' => '🍼 Alimentation', 'sleep' => '😴 Sommeil', 'hygiene' => '🧼 Hygiène'] as $section => $title)
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ $title }}</h2>
            <dl class="text-sm space-y-2">
                @if($section === 'feeding')
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Allaitement / lait artificiel</dt><dd>{{ $profile->breastfed ? 'Allaité' : '' }}{{ $profile->breastfed && $profile->formula_fed ? ' · ' : '' }}{{ $profile->formula_fed ? 'Lait artificiel' : '' }}@if(! $profile->breastfed && ! $profile->formula_fed)<span class="text-gray-400">—</span>@endif</dd></div>
                @endif
                @if($section === 'sleep')
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Tétine</dt><dd>{{ $profile->pacifier ? 'Oui' : 'Non' }}</dd></div>
                @endif
                @foreach(\App\Models\ChildCareProfile::SECTIONS[$section] as $field => $label)
                <div class="flex justify-between gap-4"><dt class="text-gray-500 shrink-0">{{ $label }}</dt><dd class="text-right whitespace-pre-line">{!! $show($profile->{$field}) !!}</dd></div>
                @endforeach
            </dl>
        </div>
        @endforeach

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">💬 Particularités</h2>
            <p class="text-sm whitespace-pre-line">{!! $show($profile->particularities) !!}</p>
            @if($profile->updatedBy)
            <p class="text-xs text-gray-400 mt-4">Mis à jour le {{ $profile->updated_at->format('d/m/Y à H:i') }} par {{ $profile->updatedBy->full_name }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
