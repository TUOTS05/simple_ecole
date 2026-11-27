@extends('layouts.app')

@section('title', 'Journée de '.$student->first_name)
@section('page_title', 'Journal quotidien')

@section('content')
@php
    $group = $student->crecheClass();
    $canFix = $canWrite && ($date->isToday() || auth()->user()->can('creche.manage'));
@endphp
<div class="max-w-2xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="flex items-center justify-between text-sm">
        <a href="{{ route('app.creche.journal', ['group' => $group?->id]) }}" class="text-primary hover:underline">← Groupe</a>
        <a href="{{ route('app.creche.children.show', $student) }}" class="text-primary hover:underline">Dossier crèche →</a>
    </div>

    <!-- En-tête et navigation par jour -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <h1 class="text-xl font-bold text-gray-900">{{ $student->first_name }} — {{ $date->isToday() ? 'Aujourd\'hui' : $date->translatedFormat('l d F Y') }}</h1>
        <div class="mt-2 flex items-center justify-between text-sm">
            <a href="{{ route('app.creche.journal.child', ['student' => $student, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="text-primary hover:underline">← Veille</a>
            @unless($date->isToday())
            <a href="{{ route('app.creche.journal.child', ['student' => $student, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="text-primary hover:underline">Lendemain →</a>
            @endunless
        </div>
        @if($student->careProfile?->hasAlerts())
        <p class="mt-2 text-sm text-red-700 font-semibold">⚠ {{ $student->careProfile->allergies ?: $student->careProfile->forbidden_foods }}</p>
        @endif

        <!-- Résumé de la journée -->
        <div class="mt-3 grid grid-cols-3 sm:grid-cols-6 gap-2 text-center text-xs">
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">🍼</div>{{ $totals['bottle'] }} biberon(s)</div>
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">🍽️</div>{{ $totals['meal'] }} repas</div>
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">🍪</div>{{ $totals['snack'] }} goûter(s)</div>
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">😴</div>{{ $totals['sleep'] ?? '—' }}</div>
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">🧷</div>{{ $totals['diaper'] }} change(s)</div>
            <div class="rounded bg-gray-50 py-2"><div class="text-lg">🎨</div>{{ $totals['activity'] }} activité(s)</div>
        </div>
    </div>

    @if($canWrite && $date->isToday() && $attendance?->isOnSite())
    <div class="flex flex-wrap gap-2">
        @foreach(['bottle', 'meal', 'snack', 'sleep', 'diaper', 'hygiene', 'activity', 'observation'] as $type)
        <a href="{{ route('app.creche.journal.create', ['type' => $type, 'group' => $group?->id, 'student' => $student->id]) }}"
           class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:border-primary">{{ \App\Models\CareEvent::TYPES[$type]['icon'] }} {{ \App\Models\CareEvent::TYPES[$type]['label'] }}</a>
        @endforeach
        <a href="{{ route('app.creche.incidents.create', ['student' => $student->id]) }}" class="px-3 py-2 bg-white border border-red-200 text-red-700 rounded-lg text-sm hover:border-red-500">⚠ Incident</a>
    </div>
    @endif
    <div class="text-right">
        <a href="{{ route('app.creche.development', $student) }}" class="text-sm text-primary hover:underline">🌱 Suivi du développement →</a>
    </div>

    <!-- Chronologie -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        @if($timeline->isEmpty())
        <p class="text-center text-gray-500 py-6">Rien d'enregistré pour cette journée.</p>
        @else
        <ol class="relative border-l-2 border-gray-200 ml-3 space-y-4">
            @foreach($timeline as $item)
            <li class="ml-5">
                <span class="absolute -left-[9px] mt-1 w-4 h-4 rounded-full border-2 border-white {{ match ($item['kind']) { 'event' => 'bg-primary', 'incident' => 'bg-red-600', default => 'bg-gray-700' } }}"></span>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="font-mono font-semibold text-gray-700">{{ str_replace(':', 'h', $item['time']) }}</span>
                        @if($item['kind'] === 'arrival')
                            <span class="ml-1">🏠 Arrivée</span>
                            @if($item['attendance']->dropped_off_by)<span class="text-gray-500"> · avec {{ $item['attendance']->dropped_off_by }}</span>@endif
                            @if($item['attendance']->is_late)<span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800">retard</span>@endif
                            @if($item['attendance']->notes)<p class="text-sm text-gray-600 italic">« {{ $item['attendance']->notes }} »</p>@endif
                        @elseif($item['kind'] === 'incident')
                            @php $incident = $item['incident']; @endphp
                            <a href="{{ route('app.creche.incidents.show', $incident) }}" class="ml-1 text-red-700 font-semibold hover:underline">⚠ Incident : {{ $incident->type_label }}</a>
                            <span class="ml-1 px-1.5 py-0.5 text-xs rounded {{ $incident->status === 'validated' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $incident->status_label }}</span>
                            <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($incident->description, 140) }}</p>
                        @elseif($item['kind'] === 'departure')
                            <span class="ml-1">🚸 Départ</span>
                            <span class="text-gray-500"> · avec {{ $item['attendance']->picked_up_by }}</span>
                        @else
                            @php $event = $item['event']; @endphp
                            <span class="ml-1">{{ $event->icon }} {{ $event->type_label }}</span>
                            @if($event->summary())<span class="text-gray-600"> · {{ $event->summary() }}</span>@endif
                            @if($event->event_type === 'sleep' && $event->endLabel())<span class="text-gray-500"> ({{ $event->startLabel() }} – {{ $event->endLabel() }})</span>@endif
                            @if($event->notes)<p class="text-sm text-gray-600">{{ $event->notes }}</p>@endif
                            <p class="text-xs text-gray-400">{{ $event->staff?->full_name }}</p>
                        @endif
                    </div>
                    @if($item['kind'] === 'event' && $canFix)
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        @if($item['event']->isSleeping() && $date->isToday())
                        <form action="{{ route('app.creche.journal.wake', $item['event']) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-2 py-1 bg-indigo-600 text-white rounded text-xs font-semibold">Réveil</button>
                        </form>
                        @endif
                        <a href="{{ route('app.creche.journal.edit', $item['event']) }}" class="text-xs text-primary hover:underline">Modifier</a>
                    </div>
                    @endif
                </div>
            </li>
            @endforeach
        </ol>
        @endif
    </div>
</div>
@endsection
