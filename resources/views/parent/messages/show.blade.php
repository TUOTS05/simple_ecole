<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $message->subject }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white min-h-screen shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 flex items-center">
            <a href="{{ route('parent.messages.index') }}" class="mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h1 class="text-lg font-bold flex-1 truncate">{{ $message->subject }}</h1>
        </div>

        <div class="p-6">
            <!-- Info du message -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
                    <span>🏫 {{ $message->school->name ?? 'École' }}</span>
                    <span>{{ $message->created_at->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="prose max-w-none text-gray-800 whitespace-pre-wrap">
                    {{ $message->message }}
                </div>
            </div>

            <!-- Réponse de l'école -->
            @if($message->reply)
                <div class="bg-green-50 border-l-4 border-green-500 rounded-xl p-4">
                    <div class="flex justify-between items-center text-sm text-gray-600 mb-2">
                        <span class="font-bold text-green-700">💬 Réponse de l'école</span>
                        <span>{{ $message->replied_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="prose max-w-none text-gray-800 whitespace-pre-wrap">
                        {{ $message->reply }}
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-xl p-4 text-center">
                    <p class="text-yellow-800 font-medium">⏳ En attente de réponse de l'école</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>