@extends('layouts.app')

@section('title', 'Présences du jour')
@section('page_title', 'Présences du jour')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
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

    <!-- Groupe et compteurs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET">
                <select name="group" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg bg-white font-semibold">
                    @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ $group?->id === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </form>
            <span class="text-sm text-gray-500">{{ now()->translatedFormat('l d F') }}</span>
            <a href="{{ route('app.creche.departure.index') }}" class="px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-semibold text-sm">🚸 Départ enfant</a>
        </div>
        <div class="grid grid-cols-4 gap-2 text-center">
            <div class="rounded-lg bg-green-50 py-2"><div class="text-xl font-bold text-green-700">{{ $counts['present'] }}</div><div class="text-xs text-gray-600">présents</div></div>
            <div class="rounded-lg bg-gray-50 py-2"><div class="text-xl font-bold text-gray-700">{{ $counts['expected'] }}</div><div class="text-xs text-gray-600">attendus</div></div>
            <div class="rounded-lg bg-red-50 py-2"><div class="text-xl font-bold text-red-700">{{ $counts['absent'] }}</div><div class="text-xs text-gray-600">absents</div></div>
            <div class="rounded-lg bg-blue-50 py-2"><div class="text-xl font-bold text-blue-700">{{ $counts['left'] }}</div><div class="text-xs text-gray-600">partis</div></div>
        </div>
        @if($group)
        <p class="text-xs text-gray-500">Horaires du groupe : {{ $group->effectiveOpeningTime() }} – {{ $group->effectiveClosingTime() }}</p>
        @endif
    </div>

    <!-- Enfants -->
    @forelse($children as $child)
    @php
        $attendance = $todayOf($child);
        $status = $attendance?->status;
        $border = match ($status) {
            'present' => 'border-l-green-500',
            'left' => 'border-l-blue-400',
            'absent', 'absent_justified' => 'border-l-red-400',
            default => 'border-l-gray-300',
        };
    @endphp
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 border-l-4 {{ $border }} p-4" x-data="{ panel: null }">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 shrink-0 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-2xl">
                @if($child->photo)
                <img src="{{ route('app.students.photo', $child) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                @else
                👶
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <a href="{{ route('app.creche.children.show', $child) }}" class="font-semibold text-gray-900 hover:underline">{{ $child->first_name }} {{ $child->last_name }}</a>
                <div class="text-sm text-gray-600">
                    @switch($status)
                        @case('present')
                            Arrivé(e) à {{ $attendance->checked_in_at?->format('H:i') }}
                            @if($attendance->dropped_off_by) · avec {{ $attendance->dropped_off_by }} @endif
                            @if($attendance->is_late)<span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800">retard</span>@endif
                            @break
                        @case('left')
                            Parti(e) à {{ $attendance->checked_out_at?->format('H:i') }} avec {{ $attendance->picked_up_by }}
                            @if($attendance->late_pickup)<span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-orange-100 text-orange-800">après la fermeture</span>@endif
                            @break
                        @case('absent')
                        @case('absent_justified')
                            {{ $attendance->status_label }}@if($attendance->absence_reason) · {{ $attendance->absence_reason }}@endif
                            @break
                        @default
                            <span class="text-gray-400">Attendu(e)</span>
                    @endswitch
                </div>
                @if($child->careProfile?->hasAlerts())
                <div class="text-xs text-red-700 font-semibold mt-0.5">⚠ {{ $child->careProfile->allergies ?: $child->careProfile->forbidden_foods }}</div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-3 flex flex-wrap gap-2">
            @if($status === 'present')
                <a href="{{ route('app.creche.departure.show', $child) }}" class="flex-1 text-center px-3 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-sm font-semibold">🚸 Départ</a>
            @else
                {{-- Aussi après un départ (retour dans la journée) ou une absence (arrivée tardive) --}}
                <button type="button" @click="panel = panel === 'arrival' ? null : 'arrival'" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold">✓ Arrivée</button>
                @if(! in_array($status, ['left', 'absent', 'absent_justified'], true))
                <button type="button" @click="panel = panel === 'absence' ? null : 'absence'" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm">Absent</button>
                @endif
            @endif
        </div>

        <!-- Arrivée -->
        <form x-show="panel === 'arrival'" x-cloak action="{{ route('app.creche.arrival', $child) }}" method="POST" class="mt-3 space-y-2 border-t pt-3">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Heure</label>
                    <input type="time" name="time" value="{{ now()->format('H:i') }}" class="w-full px-2 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Accompagné(e) par</label>
                    <input type="text" name="dropped_off_by" list="drop-{{ $child->id }}" maxlength="255" placeholder="Ex : sa mère" class="w-full px-2 py-2 border border-gray-300 rounded-lg">
                    <datalist id="drop-{{ $child->id }}">
                        @foreach($child->authorizedPickups as $person)<option value="{{ $person->full_name }} ({{ $person->relationship }})">@endforeach
                    </datalist>
                </div>
            </div>
            <input type="text" name="notes" maxlength="500" placeholder="Transmission des parents (facultatif)" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_late" value="1" class="rounded border-gray-300"> Arrivée en retard</label>
            <button type="submit" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">Enregistrer l'arrivée</button>
        </form>

        <!-- Absence -->
        <form x-show="panel === 'absence'" x-cloak action="{{ route('app.creche.absence', $child) }}" method="POST" class="mt-3 space-y-2 border-t pt-3">
            @csrf
            <input type="text" name="reason" maxlength="255" placeholder="Motif (facultatif)" class="w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="justified" value="1" class="rounded border-gray-300"> Absence justifiée (parents prévenus)</label>
            <button type="submit" class="w-full px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg font-semibold">Enregistrer l'absence</button>
        </form>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center text-gray-500">Aucun enfant dans ce groupe.</div>
    @endforelse

    @endif
</div>
@endsection
