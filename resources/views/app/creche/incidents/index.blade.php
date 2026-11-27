@extends('layouts.app')

@section('title', 'Incidents')
@section('page_title', 'Incidents')

@section('content')
@php
    $badge = fn (string $status) => match ($status) {
        'declared' => 'bg-amber-100 text-amber-800',
        'validated' => 'bg-green-100 text-green-800',
        default => 'bg-gray-200 text-gray-600',
    };
@endphp
<div class="max-w-5xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        @if($pendingCount)
        <a href="{{ route('app.creche.incidents.index', ['status' => 'declared']) }}" class="px-4 py-2 rounded-lg bg-amber-100 text-amber-800 font-semibold">
            ⚠ {{ $pendingCount }} incident(s) à valider
        </a>
        @else
        <span class="text-sm text-gray-500">Aucun incident en attente de validation.</span>
        @endif
        @canany(['creche.journal', 'creche.manage'])
        <a href="{{ route('app.creche.incidents.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">⚠ Déclarer un incident</a>
        @endcanany
    </div>

    <form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-600 mb-1">Statut</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="">Tous</option>
                @foreach(\App\Models\CareIncident::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Du</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Au</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Filtrer</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 divide-y divide-gray-100">
        @forelse($incidents as $incident)
        <a href="{{ route('app.creche.incidents.show', $incident) }}" class="block p-4 hover:bg-gray-50">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="font-semibold text-gray-900">{{ $incident->student?->first_name }} {{ $incident->student?->last_name }}</span>
                    <span class="text-gray-500">· {{ $incident->type_label }}</span>
                    @if($incident->schoolClass)<span class="text-gray-400 text-sm">· {{ $incident->schoolClass->name }}</span>@endif
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">{{ $incident->occurred_on->format('d/m/Y') }} {{ $incident->timeLabel() }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge($incident->status) }}">{{ $incident->status_label }}</span>
                </div>
            </div>
            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $incident->description }}</p>
            <p class="text-xs text-gray-400 mt-1">Déclaré par {{ $incident->reporter?->full_name ?? '—' }}</p>
        </a>
        @empty
        <p class="p-8 text-center text-gray-500">Aucun incident sur cette période.</p>
        @endforelse
    </div>
    {{ $incidents->links() }}
</div>
@endsection
