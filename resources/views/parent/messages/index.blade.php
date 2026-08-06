@extends('layouts.app')

@section('title', 'Mes Messages - Espace Parent')
@section('page_title', 'Messagerie')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- En-tête avec bouton d'action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📨 Mes Messages</h1>
            <p class="text-sm text-gray-500">Communications avec l'établissement scolaire</p>
        </div>
        <a href="{{ route('parent.messages.create') }}" class="flex-shrink-0 flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold py-2.5 px-5 rounded-lg transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nouveau message
        </a>
    </div>

    <!-- Message de succès -->
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Liste des messages -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-gray-800">Boîte de réception</h3>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($messages as $message)
                <a href="{{ route('parent.messages.show', $message->id) }}" 
                   class="block p-5 hover:bg-gray-50 transition duration-200 group">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-2">
                        <h3 class="font-bold text-gray-800 text-base group-hover:text-primary transition flex-1">
                            {{ $message->subject ?? 'Sans objet' }}
                        </h3>
                        @if($message->reply)
                            <span class="flex-shrink-0 bg-green-100 text-green-800 text-xs px-2.5 py-1 rounded-full font-medium">
                                ✓ Répondu
                            </span>
                        @else
                            <span class="flex-shrink-0 bg-yellow-100 text-yellow-800 text-xs px-2.5 py-1 rounded-full font-medium">
                                ⏳ En attente
                            </span>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ Str::limit($message->message ?? $message->content, 120) }}</p>
                    
                    <div class="flex flex-wrap justify-between items-center text-xs text-gray-500 pt-2 border-t border-gray-100">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $message->school->name ?? 'Établissement' }}
                        </span>
                        <span class="font-medium">
                            {{ \Carbon\Carbon::parse($message->created_at)->isoFormat('DD MMM YYYY à HH:mm') }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 font-semibold text-lg mb-1">Aucun message pour le moment</p>
                    <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Vous n'avez pas encore envoyé de message à l'école. N'hésitez pas à nous contacter pour toute question.</p>
                    <a href="{{ route('parent.messages.create') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Envoyer un premier message
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($messages->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                {{ $messages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection