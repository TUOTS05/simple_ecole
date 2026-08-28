@extends('layouts.app')

@section('title', 'Pointage QR')
@section('page_title', 'Pointage rapide (QR)')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 text-center">
        <div class="text-5xl mb-4">📷</div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Scanner un badge</h3>
        <p class="text-sm text-gray-500 mb-6">Utilisez un lecteur QR USB (le curseur doit être dans le champ ci-dessous), ou collez le code depuis une application de scan.</p>

        <form action="{{ route('extras.attendances.scan.store') }}" method="POST">
            @csrf
            <input type="text" name="code" autofocus required placeholder="Code scanné..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary mb-4">
            <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Valider</button>
        </form>

        <p class="text-xs text-gray-400 mt-4">1er scan du jour = arrivée, 2e scan = départ.</p>
    </div>
</div>
@endsection
