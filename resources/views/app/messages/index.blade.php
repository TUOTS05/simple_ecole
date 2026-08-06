@extends('layouts.app')

@section('title', 'Messages')
@section('page_title', 'Messages des parents')

@section('content')
    <div class="max-w-7xl mx-auto">
        
        <!-- En-tête -->
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">
                📨 Messages des parents
                @if($unreadCount > 0)
                    <span class="bg-red-500 text-white text-sm px-3 py-1 rounded-full ml-2">{{ $unreadCount }} non lu(s)</span>
                @endif
            </h1>
        </div>

        <!-- Liste des messages -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('success') }}
                    </div>
                @endif

                @forelse($messages as $message)
                    <a href="{{ route('app.messages.show', $message->id) }}" 
                       class="block border-b border-gray-200 py-4 hover:bg-gray-50 transition {{ !$message->is_read ? 'bg-blue-50' : '' }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    @if(!$message->is_read)
                                        <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                    @endif
                                    <h3 class="font-bold text-gray-800">{{ $message->subject }}</h3>
                                    @if($message->reply)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Répondu</span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">À traiter</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 mb-1">
                                    <span class="font-medium">{{ $message->sender->first_name }} {{ $message->sender->last_name }}</span>
                                    <span class="text-gray-400">• {{ $message->sender->email }}</span>
                                </p>
                                <p class="text-sm text-gray-500 line-clamp-1">{{ Str::limit($message->message, 150) }}</p>
                            </div>
                            <div class="text-xs text-gray-400 ml-4 whitespace-nowrap">
                                {{ $message->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-16 text-gray-500">
                        <p class="text-lg font-medium">Aucun message reçu</p>
                        <p class="text-sm mt-2">Les messages des parents apparaîtront ici.</p>
                    </div>
                @endforelse

                <div class="mt-6">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection