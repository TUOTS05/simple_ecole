@extends('layouts.app')

@section('title', 'Détail du message')
@section('page_title', 'Détail du message')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Bouton retour -->
    <a href="{{ route('app.messages.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 font-semibold">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Retour à la liste
    </a>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">

            <!-- Info expéditeur / destinataire -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-1">{{ $message->subject }}</h2>

                        @if(is_null($message->receiver_id))
                        {{-- ✅ CAS 1 : Message REÇU d'un parent --}}
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold">De : {{ $message->sender->first_name }} {{ $message->sender->last_name }}</span>
                            <span class="text-gray-400 mx-2">•</span>
                            <a href="mailto:{{ $message->sender->email }}" class="text-blue-600 hover:underline">{{ $message->sender->email }}</a>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Reçu le {{ $message->created_at->format('d/m/Y à H:i') }}
                        </p>
                        @else
                        {{-- ✅ CAS 2 : Message ENVOYÉ par l'école -> Une seule info simple --}}
                        <div class="border-t border-gray-200 pt-6 mt-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="font-bold text-blue-800 text-sm uppercase tracking-wide">Statut : Envoyé</p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Ce message a été transmis au parent avec succès. Aucune action de réponse n'est requise de votre part.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>

                    {{-- Badges de statut adaptés --}}
                    @if(is_null($message->receiver_id))
                    @if(!$message->is_read)
                    <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">Non lu</span>
                    @else
                    <span class="bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded-full font-medium">Traité</span>
                    @endif
                    @else
                    @if($message->is_read)
                    <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Lu par le parent
                    </span>
                    @else
                    <span class="bg-yellow-100 text-yellow-800 text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        En attente de lecture
                    </span>
                    @endif
                    @endif
                </div>
            </div>

            <!-- Contenu du message -->
            <div class="prose max-w-none text-gray-800 whitespace-pre-wrap mb-6 p-4 bg-white border border-gray-100 rounded-lg">
                {{ $message->message }}
            </div>

            {{-- ========================================== --}}
            {{-- LOGIQUE CONDITIONNELLE : RÉPONSE OU STATUT --}}
            {{-- ========================================== --}}

            @if(is_null($message->receiver_id))
            {{-- ✅ CAS 1 : Message REÇU -> On affiche la réponse ou le formulaire --}}
            @if($message->reply)
            <div class="bg-green-50 border-l-4 border-green-500 rounded-r-lg p-4 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-bold text-green-800">💬 Votre réponse</h4>
                    <span class="text-sm text-gray-600">Envoyée le {{ $message->replied_at->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="text-gray-800 whitespace-pre-wrap">
                    {{ $message->reply }}
                </div>
            </div>
            @else
            <div class="border-t border-gray-200 pt-6">
                <h4 class="font-bold text-gray-800 text-lg mb-4">Répondre au parent</h4>

                @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('app.messages.reply', $message->id) }}">
                    @csrf
                    <div class="mb-4">
                        <textarea name="reply" rows="5" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Votre réponse au parent...">{{ old('reply') }}</textarea>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('app.messages.index') }}" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-medium text-center hover:bg-gray-200 transition">
                            Annuler
                        </a>
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                            Envoyer la réponse
                        </button>
                    </div>
                </form>
            </div>
            @endif

            @else
            {{-- ✅ CAS 2 : Message ENVOYÉ -> Pas de formulaire, juste une information --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-bold text-blue-800">Message envoyé par l'établissement</p>
                            <p class="text-sm text-blue-700 mt-1">
                                Ce message a été envoyé à ce parent. Il n'y a pas d'action de réponse requise de votre part.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection