@extends('layouts.app')

@section('title', 'Incident — '.$student->first_name)
@section('page_title', 'Incident')

@section('content')
@php
    $banner = match ($incident->status) {
        'declared' => 'bg-amber-50 border-amber-300 text-amber-900',
        'validated' => 'bg-green-50 border-green-300 text-green-900',
        default => 'bg-gray-100 border-gray-300 text-gray-700',
    };
    $canFollowUp = $incident->status !== 'cancelled' && (auth()->user()->can('validateCrecheIncident', $student) || auth()->user()->can('recordCrecheJournal', $student));
@endphp
<div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <div class="flex items-center justify-between text-sm">
        <a href="{{ route('app.creche.incidents.index') }}" class="text-primary hover:underline">← Incidents</a>
        <a href="{{ route('app.creche.journal.child', ['student' => $student, 'date' => $incident->occurred_on->toDateString()]) }}" class="text-primary hover:underline">Journée de {{ $student->first_name }} →</a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <!-- Statut -->
    <div class="rounded-lg border-2 px-4 py-3 {{ $banner }}">
        <p class="font-bold">{{ mb_strtoupper($incident->status_label) }}</p>
        @if($incident->status === 'declared')
        <p class="text-sm">En attente de validation par la responsable. Il peut encore être corrigé.</p>
        @else
        <p class="text-sm">
            {{ $incident->status === 'validated' ? 'Validé' : 'Annulé' }} par {{ $incident->validator?->full_name ?? '—' }}
            le {{ $incident->validated_at?->format('d/m/Y à H:i') }}. L'incident ne peut plus être modifié.
        </p>
        @if($incident->validation_comment)<p class="text-sm mt-1 italic">« {{ $incident->validation_comment }} »</p>@endif
        @endif
    </div>

    <!-- Détails -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold text-gray-900">⚠ {{ $incident->type_label }} — {{ $student->first_name }} {{ $student->last_name }}</h1>
                <p class="text-sm text-gray-500">
                    {{ $incident->occurred_on->format('d/m/Y') }} à {{ $incident->timeLabel() }}
                    @if($incident->schoolClass) · {{ $incident->schoolClass->name }} @endif
                    · déclaré par {{ $incident->reporter?->full_name ?? '—' }} le {{ $incident->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>
            @if($canEdit)
            <a href="{{ route('app.creche.incidents.edit', $incident) }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Modifier</a>
            @endif
        </div>

        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase">Ce qui s'est passé</h2>
            <p class="whitespace-pre-line">{{ $incident->description }}</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase">Mesures prises</h2>
            <p class="whitespace-pre-line">{{ $incident->action_taken ?: '—' }}</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-500 uppercase">Personne prévenue</h2>
            <p>{{ $incident->person_informed ?: 'Personne n\'a encore été prévenue' }}@if($incident->informed_at) — le {{ $incident->informed_at->format('d/m/Y à H:i') }}@endif</p>
        </div>
    </div>

    <!-- Validation -->
    @if($canValidate && $incident->status === 'declared')
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <form action="{{ route('app.creche.incidents.validate', $incident) }}" method="POST" class="space-y-2">
            @csrf
            <h2 class="font-semibold text-gray-800">Valider</h2>
            <textarea name="comment" rows="2" maxlength="2000" placeholder="Commentaire (facultatif)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <button type="submit" class="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">✅ Valider l'incident</button>
        </form>
        <form action="{{ route('app.creche.incidents.cancel', $incident) }}" method="POST" class="space-y-2" onsubmit="return confirm('Annuler cette déclaration ?')">
            @csrf
            <h2 class="font-semibold text-gray-800">Annuler la déclaration</h2>
            <textarea name="reason" rows="2" maxlength="2000" required placeholder="Motif (obligatoire), ex : déclaré sur le mauvais enfant" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <button type="submit" class="w-full py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold">Annuler la déclaration</button>
        </form>
    </div>
    @endif

    <!-- Suivis -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        <h2 class="font-semibold text-gray-800">Suivi</h2>
        @forelse($incident->follow_ups ?? [] as $followUp)
        <div class="border-l-4 border-gray-200 pl-3">
            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($followUp['at'])->format('d/m/Y à H:i') }} — {{ $followUp['by_name'] ?? '' }}</p>
            <p class="whitespace-pre-line text-sm">{{ $followUp['text'] }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-500">Aucun suivi.</p>
        @endforelse

        @if($canFollowUp)
        <form action="{{ route('app.creche.incidents.follow-up', $incident) }}" method="POST" class="space-y-2">
            @csrf
            <textarea name="text" rows="2" maxlength="2000" required placeholder="Ex : la mère est venue chercher l'enfant à 14h, pas de rougeur" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-semibold">Ajouter un suivi</button>
        </form>
        @endif
    </div>

    <!-- Historique -->
    <details class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <summary class="font-semibold text-gray-800 cursor-pointer">Historique ({{ $history->count() }})</summary>
        <ul class="mt-3 space-y-2 text-sm">
            @foreach($history as $entry)
            <li>
                <span class="text-gray-500">{{ $entry->getRawOriginal('created_at') ? \Carbon\Carbon::parse($entry->getRawOriginal('created_at'))->format('d/m/Y H:i') : '' }}</span>
                — <strong>{{ $entry->user_name }}</strong> : {{ $entry->description }}
            </li>
            @endforeach
        </ul>
    </details>
</div>
@endsection
