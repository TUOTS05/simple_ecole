@extends('layouts.app')

@section('title', 'La journée de '.$student->first_name)
@section('page_title', 'La journée de mon enfant')

@section('content')
@php
    $presence = $day['presence'];
    $card = 'bg-white rounded-xl shadow-sm border border-gray-100 p-4';
    $h = 'text-sm font-bold text-gray-500 uppercase tracking-wider mb-2';
@endphp
<div class="max-w-2xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <a href="{{ route('parent.child.details', $student->id) }}" class="text-primary hover:underline text-sm">← Fiche de {{ $student->first_name }}</a>

    <!-- Titre et navigation -->
    <div class="{{ $card }}">
        <h1 class="text-2xl font-bold text-gray-900">{{ $student->first_name }} — {{ $date->isToday() ? 'Aujourd\'hui' : $date->translatedFormat('l d F Y') }}</h1>
        <div class="mt-2 flex items-center justify-between text-sm">
            <a href="{{ route('parent.creche.day', ['student' => $student->id, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="text-primary hover:underline">← Veille</a>
            @unless($date->isToday())
            <a href="{{ route('parent.creche.day', ['student' => $student->id, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="text-primary hover:underline">Lendemain →</a>
            @endunless
        </div>
    </div>

    @if($day['is_empty'])
    <div class="{{ $card }} text-center text-gray-500 py-8">Rien n'a encore été noté pour cette journée.</div>
    @else

    <!-- Humeur et message -->
    @if($day['published'])
    <div class="bg-pink-50 border border-pink-100 rounded-xl p-4">
        @if($day['mood'])<p class="text-xl font-semibold">{{ $day['mood']['icon'] }} {{ $day['mood']['label'] }}</p>@endif
        @if($day['message'])
        <p class="mt-2 text-gray-700 italic">« {{ $day['message'] }} »</p>
        <p class="text-xs text-gray-500 mt-1">Message de l'équipe de la crèche</p>
        @endif
    </div>
    @endif

    <!-- Présence -->
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">🏠 Présence</h2>
        @if(in_array($presence['status'], ['absent', 'absent_justified'], true))
        <p>{{ $presence['status_label'] }}@if($presence['absence_reason']) — {{ $presence['absence_reason'] }}@endif</p>
        @elseif($presence['arrived_at'])
        <p>Arrivée : <strong>{{ str_replace(':', 'h', $presence['arrived_at']) }}</strong></p>
        @if($presence['left_at'])
        <p>Départ : <strong>{{ str_replace(':', 'h', $presence['left_at']) }}</strong>@if($presence['picked_up_by']) avec {{ $presence['picked_up_by'] }}@endif</p>
        @else
        <p class="text-green-700">Toujours à la crèche</p>
        @endif
        @else
        <p class="text-gray-500">Pas encore arrivé(e).</p>
        @endif
    </div>

    <!-- Alimentation -->
    @if($day['feeding']['bottles'] || $day['feeding']['meals'] || $day['feeding']['snacks'])
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">🍽️ Alimentation</h2>
        @if($day['feeding']['bottles'])
        <p class="font-medium">{{ count($day['feeding']['bottles']) }} biberon(s) · {{ $day['feeding']['bottles_total_ml'] }} ml</p>
        @endif
        <ul class="mt-1 space-y-1 text-sm">
            @foreach([...$day['feeding']['bottles'], ...$day['feeding']['meals'], ...$day['feeding']['snacks']] as $item)
            <li><span class="font-mono text-gray-500">{{ str_replace(':', 'h', $item['time']) }}</span> {{ $item['label'] }}@if($item['summary']) — {{ $item['summary'] }}@endif
                @if($item['notes'])<span class="text-gray-500 italic"> · {{ $item['notes'] }}</span>@endif</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Sommeil -->
    @if($day['sleep']['naps'])
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">😴 Sommeil</h2>
        <p class="font-medium">{{ $day['sleep']['total_label'] ?? '—' }}@if($day['sleep']['sleeping_now']) <span class="text-indigo-700">· dort en ce moment</span>@endif</p>
        <ul class="mt-1 text-sm space-y-1">
            @foreach($day['sleep']['naps'] as $nap)
            <li><span class="font-mono text-gray-500">{{ str_replace(':', 'h', $nap['time']) }}{{ $nap['end'] ? ' – '.str_replace(':', 'h', $nap['end']) : '' }}</span> {{ $nap['summary'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Hygiène -->
    @if($day['hygiene']['diapers'] || $day['hygiene']['cares'])
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">🧼 Hygiène</h2>
        <p>@if($day['hygiene']['diapers']){{ $day['hygiene']['diapers'] }} change(s)@endif
           @if($day['hygiene']['diapers'] && $day['hygiene']['cares']) · @endif
           @if($day['hygiene']['cares']){{ $day['hygiene']['cares'] }} soin(s)@endif</p>
    </div>
    @endif

    <!-- Activités -->
    @if($day['activities'])
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">🎨 Activités</h2>
        <ul class="text-sm space-y-1">
            @foreach($day['activities'] as $activity)
            <li>{{ $activity['summary'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Observations -->
    @if($day['observations'])
    <div class="{{ $card }}">
        <h2 class="{{ $h }}">📝 Observations</h2>
        <ul class="text-sm space-y-1">
            @foreach($day['observations'] as $observation)
            <li><span class="font-mono text-gray-500">{{ str_replace(':', 'h', $observation['time']) }}</span> {{ $observation['text'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Incidents validés -->
    @foreach($day['incidents'] as $incident)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <h2 class="font-semibold text-amber-900">⚠ {{ $incident['type'] }} à {{ str_replace(':', 'h', $incident['time']) }}</h2>
        <p class="text-sm mt-1">{{ $incident['description'] }}</p>
        @if($incident['action_taken'])<p class="text-sm mt-1"><strong>Ce que nous avons fait :</strong> {{ $incident['action_taken'] }}</p>@endif
        <p class="text-xs text-amber-800 mt-2">Pour toute question, contactez la crèche.</p>
    </div>
    @endforeach

    @endif
</div>
@endsection
