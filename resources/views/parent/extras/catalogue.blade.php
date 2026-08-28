@extends('layouts.app')

@section('title', 'Catalogue Extras - ' . $student->first_name)
@section('page_title', 'Services disponibles')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🧩 Services disponibles</h1>
            <p class="text-sm text-gray-500">Pour {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <a href="{{ route('parent.extras.index', $student->id) }}" class="text-primary hover:underline text-sm font-semibold">← Mes extras</a>
    </div>

    @if(! $enrollment)
    <div class="text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200 border-dashed">
        Votre enfant n'a pas d'inscription active cette année, impossible de consulter les tarifs.
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse($extras as $extra)
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-bold text-gray-800">{{ $extra->category->icon ?? '🧩' }} {{ $extra->name }}</h3>
                @if($extra->seats_left !== null)
                <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $extra->seats_left > 0 ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                    {{ $extra->seats_left > 0 ? $extra->seats_left.' places restantes' : "Complet — liste d'attente" }}
                </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mb-3">{{ $extra->description ?: 'Aucune description.' }}</p>

            @if($extra->destination || $extra->registration_deadline)
            <div class="text-xs text-gray-500 mb-3 space-y-0.5">
                @if($extra->destination)<p>📍 Destination : {{ $extra->destination }}</p>@endif
                @if($extra->includes_transport)<p>🚌 Transport inclus</p>@endif
                @if($extra->requires_parental_authorization)<p>✍️ Autorisation parentale requise</p>@endif
                @if($extra->registration_deadline)
                <p class="{{ ! $extra->isRegistrationOpen() ? 'text-red-600 font-semibold' : '' }}">
                    ⏳ Inscriptions jusqu'au {{ $extra->registration_deadline->format('d/m/Y') }}
                </p>
                @endif
            </div>
            @endif

            @if($extra->applicable_tarif)
            <p class="text-lg font-bold text-primary mb-3">
                {{ number_format($extra->applicable_tarif->amount, 0, ',', ' ') }} FCFA
                <span class="text-xs font-normal text-gray-500">{{ $extra->billing_type === 'recurring' ? '/ période' : '(frais unique)' }}</span>
            </p>

            @if(! $extra->isRegistrationOpen())
            <p class="text-center text-sm text-red-600 font-semibold py-2">Date limite d'inscription dépassée</p>
            @else
            <form action="{{ route('parent.extras.request', ['student' => $student->id, 'extraId' => $extra->id]) }}" method="POST" onsubmit="return confirm('{{ $extra->seats_left === 0 ? 'Ce service est complet : rejoindre la liste d\'attente ?' : 'Envoyer une demande d\'inscription à ce service ?' }}')">
                @csrf
                <button type="submit"
                    class="w-full {{ $extra->seats_left === 0 ? 'bg-purple-600 hover:bg-purple-700' : 'bg-primary hover:bg-primary-dark' }} text-white px-4 py-2 rounded-lg font-semibold text-sm transition">
                    {{ $extra->seats_left === 0 ? "Rejoindre la liste d'attente" : "Demander l'inscription" }}
                </button>
            </form>
            @endif
            @else
            <p class="text-sm text-orange-600 italic">Tarif non encore disponible pour la classe de votre enfant.</p>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-10 text-gray-500 bg-white rounded-xl border border-gray-200 border-dashed">
            Aucun nouveau service disponible pour le moment.
        </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
