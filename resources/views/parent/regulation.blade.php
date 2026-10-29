@extends('layouts.app')

@section('title', 'Règlement intérieur')
@section('page_title', 'Règlement intérieur')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-4 mb-6 rounded">{{ session('error') }}</div>
    @endif

    @if(! $regulation)
        <div class="bg-white rounded-xl shadow-sm p-6 text-gray-600">L'école n'a pas encore publié de règlement intérieur.</div>
    @else
        <div class="bg-white rounded-xl shadow-sm p-6">
            <p class="text-sm text-gray-500">Version {{ $regulation->version }} — en vigueur depuis le {{ $regulation->created_at->format('d/m/Y') }}</p>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ $regulation->title }}</h2>

            @if($regulation->content)
            <div class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $regulation->content }}</div>
            @endif

            @if($regulation->file_path)
            <a href="{{ route('parent.regulation.download') }}" class="inline-block mt-4 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-sm">📎 Télécharger le règlement (PDF)</a>
            @endif
        </div>

        @if($acceptance)
            <div class="mt-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded">
                ✅ Vous avez accepté ce règlement le {{ $acceptance->accepted_at->format('d/m/Y à H:i') }}.
            </div>
        @elseif($regulation->requires_acceptance)
            <form action="{{ route('parent.regulation.accept') }}" method="POST" class="mt-6 bg-white rounded-xl shadow-sm p-6"
                  x-data="{ read: false }">
                @csrf
                <input type="hidden" name="version" value="{{ $regulation->version }}">
                <label class="flex items-start cursor-pointer">
                    <input type="checkbox" x-model="read" class="w-5 h-5 mt-0.5 text-primary rounded focus:ring-primary">
                    <span class="ml-3 text-gray-700">J'ai lu le règlement intérieur de l'établissement et je m'engage à le respecter et à le faire respecter par mon/mes enfant(s).</span>
                </label>
                <button type="submit" :disabled="!read" class="mt-4 w-full sm:w-auto bg-primary hover:bg-primary-dark disabled:opacity-50 text-white px-6 py-3 rounded-lg font-semibold transition">
                    J'accepte le règlement
                </button>
            </form>
        @endif
    @endif
</div>
@endsection
