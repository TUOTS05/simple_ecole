@extends('layouts.app')

@section('title', $message->subject . ' - Espace Parent')
@section('page_title', 'Détail du message')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <a href="{{ route('parent.messages.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-primary transition mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Retour à la messagerie
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ $message->subject }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- En-tête du message -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">
                        @if($message->sender_id === auth()->id())
                            Vous (Parent)
                        @else
                            {{ $message->school->name ?? 'Administration' }}
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 flex items-center gap-2 flex-wrap mt-1">
                        <span>{{ \Carbon\Carbon::parse($message->created_at)->isoFormat('DD MMM YYYY à HH:mm') }}</span>
                        
                        {{-- ✅ AJOUT CRUCIAL : Badge de contexte pour savoir de quelle classe il s'agit --}}
                        @if($message->target_info)
                            <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wide border border-indigo-200">
                                🏫 {{ $message->target_info }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            
            @if($message->sender_id === auth()->id())
                <span class="flex-shrink-0 bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded-full font-medium">📤 Message envoyé</span>
            @else
                <span class="flex-shrink-0 bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">📥 Message reçu</span>
            @endif
        </div>

        <!-- Corps du message -->
        <div class="p-6">
            <div class="prose max-w-none text-gray-800 whitespace-pre-wrap leading-relaxed">
                {{ $message->message ?? $message->body ?? 'Aucun contenu' }}
            </div>
        </div>

        <!-- Réponse de l'école (si applicable) -->
        @if($message->reply)
            <div class="border-t border-gray-100 bg-green-50/50 p-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="bg-green-100 text-green-700 p-1.5 rounded-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                    </span>
                    <span class="font-bold text-green-800">Réponse de l'école</span>
                    <span class="text-xs text-gray-500 ml-auto">
                        {{ \Carbon\Carbon::parse($message->updated_at)->isoFormat('DD MMM YYYY à HH:mm') }}
                    </span>
                </div>
                <div class="prose max-w-none text-gray-800 whitespace-pre-wrap leading-relaxed bg-white p-4 rounded-lg border border-green-100">
                    {{ $message->reply }}
                </div>
            </div>
        @elseif($message->sender_id === auth()->id())
            <div class="border-t border-gray-100 bg-yellow-50/50 p-6 text-center">
                <div class="inline-flex items-center gap-2 text-yellow-800 font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    En attente de réponse de l'école
                </div>
            </div>
        @endif
    </div>
</div>
@endsection