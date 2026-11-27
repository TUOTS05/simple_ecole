@extends('layouts.app')

@section('title', 'Départ — '.$student->first_name.' '.$student->last_name)
@section('page_title', 'Départ enfant')

@section('content')
@php
    $onSite = $attendance?->status === 'present';
    $selected = old('pickup_id');
@endphp
<div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <a href="{{ route('app.creche.departure.index') }}" class="text-primary hover:underline text-sm">← Autre enfant</a>

    {{-- Résultat de la vérification --}}
    @if($result && $result['allowed'])
    <div class="rounded-xl bg-green-600 text-white p-6 text-center shadow-lg">
        <div class="text-4xl font-extrabold tracking-wide">✅ DÉPART AUTORISÉ</div>
        <p class="mt-2 text-lg">
            {{ $student->first_name }} est parti(e) à {{ $attendance?->checked_out_at?->format('H:i') }} avec {{ $attendance?->picked_up_by }}.
        </p>
        @if($attendance?->late_pickup)
        <p class="mt-2 inline-block bg-white/20 rounded px-3 py-1">⏰ Récupération après l'heure de fermeture ({{ $group?->effectiveClosingTime() }})</p>
        @endif
    </div>
    @elseif($result)
    <div class="rounded-xl bg-red-600 text-white p-6 text-center shadow-lg" role="alert">
        <div class="text-4xl font-extrabold tracking-wide">⛔ DÉPART BLOQUÉ</div>
        <p class="mt-2 text-lg font-semibold">{{ $result['reason'] }}</p>
        <p class="mt-3">Ne confiez pas l'enfant. Prévenez la direction et contactez les parents :</p>
        <p class="mt-1 font-semibold">
            @if($student->mother_phone) Mère : {{ $student->mother_phone }} @endif
            @if($student->father_phone) · Père : {{ $student->father_phone }} @endif
            @if($student->guardian_phone) · Responsable : {{ $student->guardian_phone }} @endif
        </p>
        <p class="mt-2 text-sm opacity-80">La tentative a été inscrite au journal.</p>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <!-- L'enfant -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex items-center gap-4">
        <div class="w-28 h-28 shrink-0 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center text-5xl">
            @if($student->photo)
            <img src="{{ route('app.students.photo', $student) }}" alt="Photo de {{ $student->first_name }}" class="w-full h-full object-cover">
            @else
            👶
            @endif
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $student->first_name }} {{ strtoupper($student->last_name) }}</h1>
            <p class="text-gray-600">{{ $group?->name }} · {{ $student->matricule }}</p>
            <p class="text-sm mt-1">
                @if($onSite)
                <span class="text-green-700 font-semibold">Présent(e) depuis {{ $attendance->checked_in_at?->format('H:i') }}</span>
                @elseif($attendance?->status === 'left')
                <span class="text-blue-700 font-semibold">Parti(e) à {{ $attendance->checked_out_at?->format('H:i') }} avec {{ $attendance->picked_up_by }}</span>
                @else
                <span class="text-gray-500">Pas pointé(e) présent(e) aujourd'hui</span>
                @endif
            </p>
        </div>
    </div>

    @if($onSite)
    <!-- Qui vient chercher l'enfant ? -->
    <form action="{{ route('app.creche.departure.store', $student) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3"
          x-data="{ choice: @js($selected) }">
        @csrf
        <h2 class="text-lg font-semibold text-gray-800">Qui vient chercher {{ $student->first_name }} ?</h2>

        @foreach($pickups as $pickup)
        @php
            $valid = $pickup->isAuthorizedOn();
            $state = $pickup->stateLabel();
        @endphp
        <label class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer {{ $pickup->status === 'forbidden' ? 'border-red-300 bg-red-50' : ($valid ? 'border-gray-200 hover:border-green-400' : 'border-gray-200 bg-gray-50') }}"
               :class="choice === '{{ $pickup->id }}' ? 'ring-2 ring-primary' : ''">
            <input type="radio" name="pickup_id" value="{{ $pickup->id }}" x-model="choice" class="w-5 h-5">
            <div class="w-16 h-16 shrink-0 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-2xl">
                @if($pickup->photo)
                <img src="{{ route('app.creche.pickups.photo', $pickup) }}" alt="Photo de {{ $pickup->full_name }}" class="w-full h-full object-cover">
                @else
                👤
                @endif
            </div>
            <div class="flex-1">
                <div class="font-semibold">{{ $pickup->full_name }} <span class="font-normal text-gray-500">— {{ $pickup->relationship }}</span></div>
                @if($pickup->phone)<div class="text-sm text-gray-600">📞 {{ $pickup->phone }}</div>@endif
                @if($pickup->comment)<div class="text-xs text-gray-500 italic">{{ $pickup->comment }}</div>@endif
            </div>
            <span class="px-2 py-1 text-xs rounded-full font-bold {{ $pickup->status === 'forbidden' ? 'bg-red-600 text-white' : ($valid ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700') }}">
                {{ $pickup->status === 'forbidden' ? 'NON AUTORISÉE' : mb_strtoupper($state) }}
            </span>
        </label>
        @endforeach

        <label class="flex items-start gap-3 p-3 rounded-lg border-2 border-dashed border-gray-300 cursor-pointer" :class="choice === 'other' ? 'ring-2 ring-primary' : ''">
            <input type="radio" name="pickup_id" value="other" x-model="choice" class="w-5 h-5 mt-1">
            <div class="flex-1">
                <div class="font-semibold">Une autre personne</div>
                <div class="text-sm text-gray-500">Absente de la liste : le départ sera bloqué.</div>
                <input type="text" name="other_name" value="{{ old('other_name') }}" maxlength="150" x-show="choice === 'other'" placeholder="Nom de la personne qui se présente"
                       class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </label>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="identity_checked" value="1" class="rounded border-gray-300">
            J'ai vérifié l'identité de la personne (photo ou pièce d'identité)
        </label>

        <button type="submit" :disabled="! choice" class="w-full px-4 py-4 bg-pink-600 hover:bg-pink-700 disabled:opacity-50 text-white rounded-lg text-lg font-bold">
            Vérifier et enregistrer le départ
        </button>
    </form>
    @elseif(! $attendance || $attendance->isAbsent())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg px-4 py-3">
        {{ $student->first_name }} n'est pas pointé(e) présent(e) : enregistrez d'abord son arrivée dans
        <a href="{{ route('app.creche.today', ['group' => $group?->id]) }}" class="underline font-semibold">Présences du jour</a>.
    </div>
    @endif

    @if($student->careProfile?->hasAlerts())
    <p class="text-sm text-red-700">⚠ {{ $student->careProfile->allergies ?: $student->careProfile->forbidden_foods }}</p>
    @endif
</div>
@endsection
