@extends('layouts.app')

@section('title', 'Nouveau membre du personnel')
@section('page_title', 'Ajouter un membre du personnel')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('app.staff.store') }}" method="POST">
                @csrf
                @include('app.staff._form')

                <div class="mt-8 flex space-x-4">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition">Enregistrer</button>
                    <a href="{{ route('app.staff.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-semibold transition">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
