@extends('layouts.app')

@section('title', 'Nouveau message - Espace Parent')
@section('page_title', 'Nouveau message')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <a href="{{ route('parent.messages.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-primary transition mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Retour à la messagerie
        </a>
        <h1 class="text-2xl font-bold text-gray-800">✉️ Nouveau message</h1>
        <p class="text-sm text-gray-500">Envoyez une communication à l'établissement scolaire</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start shadow-sm">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('parent.messages.store') }}">
            @csrf
            
            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-semibold mb-2">École destinataire <span class="text-red-500">*</span></label>
                @if($schools->count() > 1)
                    <select name="school_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
                        <option value="">-- Choisir une école --</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @elseif($schools->count() == 1)
                    <input type="hidden" name="school_id" value="{{ $schools->first()->id }}">
                    <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 font-medium">{{ $schools->first()->name }}</div>
                @else
                    <div class="text-red-600 text-sm bg-red-50 p-3 rounded-lg">Aucune école associée à vos enfants.</div>
                @endif
            </div>

            <div class="mb-5">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Sujet <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                       placeholder="Ex: Question sur les frais de scolarité, Absence prévue...">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2">Message <span class="text-red-500">*</span></label>
                <textarea name="message" rows="6" required 
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                          placeholder="Décrivez votre demande ou votre question en détail (minimum 10 caractères)...">{{ old('message') }}</textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('parent.messages.index') }}" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-semibold text-center hover:bg-gray-200 transition">Annuler</a>
                <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Envoyer le message
                </button>
            </div>
        </form>
    </div>
</div>
@endsection