@extends('layouts.app')

@section('title', 'Départ enfant')
@section('page_title', 'Départ enfant')

@section('content')
<div class="max-w-3xl mx-auto px-3 sm:px-6 py-4 space-y-4">

    <form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex gap-2">
        <input type="search" name="search" value="{{ $search }}" autofocus placeholder="Nom, prénom ou matricule de l'enfant"
               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-lg">
        <button type="submit" class="px-5 py-3 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold">Rechercher</button>
    </form>

    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
        {{ $search === '' ? 'Enfants encore présents' : 'Résultats pour « '.$search.' »' }} ({{ $children->count() }})
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @forelse($children as $child)
        @php $attendance = $child->crecheAttendances->first(); @endphp
        <a href="{{ route('app.creche.departure.show', $child) }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-3 flex items-center gap-3 hover:border-primary">
            <div class="w-14 h-14 shrink-0 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center text-2xl">
                @if($child->photo)
                <img src="{{ route('app.students.photo', $child) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                @else
                👶
                @endif
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-gray-900">{{ $child->first_name }} {{ $child->last_name }}</div>
                <div class="text-sm text-gray-500">{{ $child->crecheClass()?->name }}</div>
                <div class="text-xs mt-0.5">
                    @if($attendance?->status === 'present')
                    <span class="text-green-700">Présent(e) depuis {{ $attendance->checked_in_at?->format('H:i') }}</span>
                    @elseif($attendance?->status === 'left')
                    <span class="text-blue-700">Déjà parti(e) à {{ $attendance->checked_out_at?->format('H:i') }}</span>
                    @else
                    <span class="text-gray-400">Pas pointé(e) présent(e) aujourd'hui</span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <p class="text-gray-500 col-span-full text-center py-8">
            {{ $search === '' ? 'Aucun enfant n\'est présent pour le moment.' : 'Aucun enfant trouvé dans vos groupes.' }}
        </p>
        @endforelse
    </div>
</div>
@endsection
