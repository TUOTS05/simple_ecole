@extends('layouts.app')

@section('title', 'Modifier la classe')
@section('page_title', 'Modifier la classe')

@section('content')

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">

            <form action="{{ route('app.classes.update', $class) }}" method="POST">
                @csrf
                @method('PUT')

                @include('app.classes._form')

                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">
                        Mettre à jour
                    </button>
                    <a href="{{ route('app.classes.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">
                        Annuler
                    </a>
                </div>

            </form>

        </div>
    </div>

@endsection
