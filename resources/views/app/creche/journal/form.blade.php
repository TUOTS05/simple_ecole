@extends('layouts.app')

@php
    $isEdit = $event->exists;
    $meta = \App\Models\CareEvent::TYPES[$type];
    $title = ($isEdit ? 'Modifier — ' : 'Nouveau : ').$meta['label'];
    $input = 'w-full px-3 py-3 border border-gray-300 rounded-lg';
    $val = fn (string $field, $default = null) => old($field, $default);
    $d = fn (string $key) => $event->datum($key);
    $chips = 'px-3 py-2 rounded-lg border-2 text-sm cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/10';
@endphp

@section('title', $title)
@section('page_title', $title)

@section('content')
<div class="max-w-2xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <a href="{{ $isEdit ? route('app.creche.journal.child', ['student' => $candidates->first(), 'date' => $event->event_date->toDateString()]) : route('app.creche.journal', ['group' => $group?->id]) }}" class="text-primary hover:underline text-sm">← Retour</a>

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form action="{{ $isEdit ? route('app.creche.journal.update', $event) : route('app.creche.journal.store') }}" method="POST"
          class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-4">
        @csrf
        @if($isEdit) @method('PUT') @else
        <input type="hidden" name="event_type" value="{{ $type }}">
        <input type="hidden" name="group" value="{{ $group?->id }}">
        @endif

        <h1 class="text-xl font-bold text-gray-900">{{ $meta['icon'] }} {{ $meta['label'] }}</h1>

        <!-- Enfant(s) -->
        @if($isEdit)
        <p class="font-semibold">{{ $candidates->first()->first_name }} {{ $candidates->first()->last_name }} · {{ $event->event_date->format('d/m/Y') }}</p>
        @else
        <div x-data="{ toggleAll(on) { $root.querySelectorAll('input[name=\'student_ids[]\']').forEach(c => c.checked = on) } }">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">{{ $onlyPresent ? 'Enfants présents' : 'Enfants du groupe' }}</label>
                <span class="text-xs space-x-2">
                    <button type="button" @click="toggleAll(true)" class="text-primary hover:underline">Tous</button>
                    <button type="button" @click="toggleAll(false)" class="text-gray-500 hover:underline">Aucun</button>
                </span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($candidates as $child)
                <label class="{{ $chips }} border-gray-200">
                    <input type="checkbox" name="student_ids[]" value="{{ $child->id }}" class="sr-only"
                           {{ in_array($child->id, array_map('intval', (array) old('student_ids', $selected)), true) ? 'checked' : '' }}>
                    {{ $child->first_name }}
                    @if($child->careProfile?->hasAlerts())<span class="text-red-600" title="Allergie / interdit alimentaire">⚠</span>@endif
                </label>
                @endforeach
            </div>
            @if(! $onlyPresent)
            <p class="text-xs text-amber-700 mt-1">Aucun enfant n'est pointé présent : tous les enfants du groupe sont proposés.</p>
            @endif
        </div>
        @endif

        <!-- Heure(s) -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $type === 'sleep' ? 'Coucher' : 'Heure' }}</label>
                <input type="time" name="start_time" value="{{ $val('start_time', $event->startLabel()) }}" required class="{{ $input }}">
            </div>
            @if($type === 'sleep')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Réveil <span class="text-gray-400">(vide = dort encore)</span></label>
                <input type="time" name="end_time" value="{{ $val('end_time', $event->endLabel()) }}" class="{{ $input }}">
            </div>
            @endif
        </div>

        @switch($type)
            @case('bottle')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantité préparée (ml) *</label>
                        <input type="number" name="quantity" value="{{ $val('quantity', $event->quantity) }}" min="1" max="1000" step="5" required inputmode="numeric" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantité bue (ml)</label>
                        <input type="number" name="consumed" value="{{ $val('consumed', $d('consumed')) }}" min="0" max="1000" step="5" inputmode="numeric" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lait</label>
                        <input type="text" name="milk" value="{{ $val('milk', $d('milk') ?? ($candidates->count() === 1 ? $candidates->first()->careProfile?->milk_type : null)) }}" maxlength="100" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Préparation</label>
                        <input type="text" name="preparation" value="{{ $val('preparation', $d('preparation')) }}" maxlength="150" placeholder="Ex : 5 mesures, tiède" class="{{ $input }}">
                    </div>
                </div>
                @break

            @case('meal')
            @case('snack')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $type === 'meal' ? 'Repas' : 'Goûter' }}</label>
                    <input type="text" name="menu" value="{{ $val('menu', $d('menu')) }}" maxlength="150" class="{{ $input }}">
                    @if(! $isEdit && $d('menu'))<p class="text-xs text-gray-500 mt-1">Repris du menu de la cantine du jour.</p>@endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">A mangé *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\CareEvent::APPRECIATIONS as $key => $label)
                        <label class="{{ $chips }} border-gray-200">
                            <input type="radio" name="appreciation" value="{{ $key }}" class="sr-only" {{ $val('appreciation', $d('appreciation')) === $key ? 'checked' : '' }}> {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @break

            @case('sleep')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualité</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\CareEvent::SLEEP_QUALITIES as $key => $label)
                        <label class="{{ $chips }} border-gray-200">
                            <input type="radio" name="quality" value="{{ $key }}" class="sr-only" {{ $val('quality', $d('quality')) === $key ? 'checked' : '' }}> {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @break

            @case('diaper')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Models\CareEvent::DIAPER_KINDS as $key => $label)
                        <label class="{{ $chips }} border-gray-200">
                            <input type="radio" name="kind" value="{{ $key }}" class="sr-only" {{ $val('kind', $d('kind')) === $key ? 'checked' : '' }}> {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Produit utilisé</label>
                    <input type="text" name="product" value="{{ $val('product', $d('product')) }}" maxlength="150" class="{{ $input }}">
                </div>
                @break

            @case('hygiene')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Soin *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach((array) $school->setting('creche.hygiene_types') as $kind)
                        <label class="{{ $chips }} border-gray-200">
                            <input type="radio" name="kind" value="{{ $kind }}" class="sr-only" {{ $val('kind', $d('kind')) === $kind ? 'checked' : '' }}> {{ $kind }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @break

            @case('activity')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach((array) $school->setting('creche.activity_categories') as $category)
                        <label class="{{ $chips }} border-gray-200">
                            <input type="radio" name="category" value="{{ $category }}" class="sr-only" {{ $val('category', $d('category')) === $category ? 'checked' : '' }}> {{ $category }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Activité</label>
                        <input type="text" name="title" value="{{ $val('title', $d('title')) }}" maxlength="150" placeholder="Ex : Comptine « Une souris verte »" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durée (min)</label>
                        <input type="number" name="duration" value="{{ $val('duration', $event->event_type === 'activity' ? $event->quantity : null) }}" min="1" max="480" inputmode="numeric" class="{{ $input }}">
                    </div>
                </div>
                @break
        @endswitch

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ in_array($type, ['observation', 'other'], true) ? 'Observation *' : 'Observation (facultatif)' }}</label>
            <textarea name="notes" rows="{{ in_array($type, ['observation', 'other'], true) ? 4 : 2 }}" maxlength="1000" class="{{ $input }}" @required(in_array($type, ['observation', 'other'], true))>{{ $val('notes', $event->notes) }}</textarea>
        </div>

        <button type="submit" class="w-full py-4 bg-primary hover:bg-primary-dark text-white rounded-lg text-lg font-bold">Enregistrer</button>
    </form>

    @if($isEdit)
    <form action="{{ route('app.creche.journal.destroy', $event) }}" method="POST" onsubmit="return confirm('Supprimer cet événement ?')" class="text-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer cet événement</button>
    </form>
    @endif
</div>
@endsection
