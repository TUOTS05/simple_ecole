@extends('layouts.app')

@section('title', 'Journal du groupe')
@section('page_title', 'Journal du groupe')

@section('content')
@php
    $quick = ['bottle', 'meal', 'snack', 'sleep', 'diaper', 'hygiene', 'activity', 'observation'];
    $canWrite = auth()->user()->can('creche.journal') || auth()->user()->can('creche.manage');
@endphp
<div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    @if($groups->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center text-gray-500">
        Vous n'êtes affecté(e) à aucun groupe de crèche. Demandez à la direction de vous affecter à un groupe.
    </div>
    @else

    <!-- En-tête -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Bonjour {{ auth()->user()->first_name }}</p>
                <form method="GET">
                    <select name="group" onchange="this.form.submit()" class="mt-1 px-3 py-2 border border-gray-300 rounded-lg bg-white font-bold uppercase">
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ $group?->id === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="text-right text-sm">
                <div><strong>{{ $children->count() }}</strong> enfants · <strong class="text-green-700">{{ $present->count() }}</strong> présents</div>
                <a href="{{ route('app.creche.today', ['group' => $group?->id]) }}" class="text-primary hover:underline">Arrivées / absences →</a>
            </div>
        </div>
    </div>

    @if($canWrite)
    <!-- Saisie rapide -->
    <div class="grid grid-cols-4 gap-2">
        @foreach($quick as $type)
        <a href="{{ route('app.creche.journal.create', ['type' => $type, 'group' => $group?->id]) }}"
           class="bg-white rounded-lg shadow-sm border border-gray-100 py-3 text-center hover:border-primary">
            <div class="text-2xl">{{ $types[$type]['icon'] }}</div>
            <div class="text-xs font-semibold text-gray-700 mt-1">{{ $types[$type]['label'] }}</div>
        </a>
        @endforeach
    </div>
    <div class="grid grid-cols-2 gap-2">
        <a href="{{ route('app.creche.incidents.create') }}" class="block text-center bg-white border-2 border-red-300 text-red-700 hover:bg-red-50 rounded-lg py-3 font-semibold">⚠ Incident</a>
        <a href="{{ route('app.creche.departure.index') }}" class="block text-center bg-pink-600 hover:bg-pink-700 text-white rounded-lg py-3 font-semibold">🚸 Départ enfant</a>
    </div>
    @endif

    <!-- À faire -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        <h2 class="font-bold text-gray-800">À FAIRE MAINTENANT</h2>

        @if($todo['sleeping']->isNotEmpty())
        <div>
            <p class="text-sm font-semibold text-indigo-700">😴 {{ $todo['sleeping']->count() }} {{ $todo['sleeping']->count() > 1 ? 'enfants dorment' : 'enfant dort' }}</p>
            <ul class="mt-1 space-y-1">
                @foreach($todo['sleeping'] as $child)
                @php $sleep = $child->careEvents->first(fn ($e) => $e->isSleeping()); @endphp
                <li class="flex items-center justify-between text-sm">
                    <span>{{ $child->first_name }} <span class="text-gray-500">depuis {{ $sleep->startLabel() }}</span></span>
                    @if($canWrite)
                    <form action="{{ route('app.creche.journal.wake', $sleep) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold">Réveil</button>
                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($todo['diaper']->isNotEmpty())
        <div>
            <p class="text-sm font-semibold text-amber-700">🧷 {{ $todo['diaper']->count() }} change(s) à prévoir</p>
            <div class="mt-1 flex flex-wrap gap-2">
                @foreach($todo['diaper'] as $child)
                <a href="{{ route('app.creche.journal.create', ['type' => 'diaper', 'group' => $group->id, 'student' => $child->id]) }}" class="px-2 py-1 rounded bg-amber-50 border border-amber-200 text-sm">{{ $child->first_name }}</a>
                @endforeach
            </div>
        </div>
        @endif

        @foreach(['meal' => '🍽️ repas à renseigner', 'snack' => '🍪 goûter(s) à renseigner'] as $type => $label)
        @if($todo[$type]->isNotEmpty())
        <a href="{{ route('app.creche.journal.create', ['type' => $type, 'group' => $group->id]) }}" class="block text-sm font-semibold text-orange-700 hover:underline">
            {{ $todo[$type]->count() }} {{ $label }} : {{ $todo[$type]->pluck('first_name')->implode(', ') }}
        </a>
        @endif
        @endforeach

        @if($todo['sleeping']->isEmpty() && $todo['diaper']->isEmpty() && $todo['meal']->isEmpty() && $todo['snack']->isEmpty())
        <p class="text-sm text-gray-500">Rien d'urgent pour le moment.</p>
        @endif
    </div>

    <!-- Enfants présents -->
    <div class="space-y-2">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Enfants présents</h2>
        @forelse($present as $child)
        @php
            $events = $child->careEvents;
            $sleepMinutes = $events->where('event_type', 'sleep')->sum(fn ($e) => $e->durationMinutes() ?? 0);
        @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-3">
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('app.creche.journal.child', $child) }}" class="font-semibold text-gray-900 hover:underline">{{ $child->first_name }} {{ $child->last_name }}</a>
                <div class="flex gap-2 text-xs text-gray-600">
                    @if($n = $events->where('event_type', 'bottle')->count())<span>🍼 {{ $n }}</span>@endif
                    @if($n = $events->whereIn('event_type', ['meal', 'snack'])->count())<span>🍽️ {{ $n }}</span>@endif
                    @if($sleepMinutes)<span>😴 {{ \App\Models\CareEvent::formatDuration($sleepMinutes) }}</span>@endif
                    @if($n = $events->where('event_type', 'diaper')->count())<span>🧷 {{ $n }}</span>@endif
                    @if($n = $events->where('event_type', 'activity')->count())<span>🎨 {{ $n }}</span>@endif
                </div>
            </div>
            @if($child->careProfile?->hasAlerts())
            <p class="text-xs text-red-700 font-semibold mt-1">⚠ {{ $child->careProfile->allergies ?: $child->careProfile->forbidden_foods }}</p>
            @endif
            @if($canWrite)
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach(['bottle', 'meal', 'sleep', 'diaper', 'observation'] as $type)
                <a href="{{ route('app.creche.journal.create', ['type' => $type, 'group' => $group->id, 'student' => $child->id]) }}"
                   title="{{ $types[$type]['label'] }}" class="px-2 py-1 border border-gray-200 rounded text-sm hover:bg-gray-50">{{ $types[$type]['icon'] }}</a>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <p class="text-sm text-gray-500 bg-white rounded-lg border border-gray-100 p-4 text-center">
            Aucun enfant présent pour le moment. Enregistrez les arrivées dans <a href="{{ route('app.creche.today', ['group' => $group?->id]) }}" class="text-primary underline">Présences du jour</a>.
        </p>
        @endforelse
    </div>
    @endif
</div>
@endsection
