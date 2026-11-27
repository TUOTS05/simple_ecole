@extends('layouts.app')

@section('title', 'Nouvelle Classe')
@section('page_title', 'Nouvelle Classe')

@section('content')

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">

            <form action="{{ route('app.classes.store') }}" method="POST">
                @csrf

                @include('app.classes._form')

                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Créer la classe
                    </button>
                    <a href="{{ route('app.classes.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>

            </form>

        </div>
    </div>

@endsection
