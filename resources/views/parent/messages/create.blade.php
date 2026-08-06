<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white min-h-screen shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 flex items-center">
            <a href="{{ route('parent.messages.index') }}" class="mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h1 class="text-lg font-bold">Nouveau message</h1>
        </div>

        <div class="p-6">
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('parent.messages.store') }}">
                @csrf
                
                <!-- Sélection de l'école -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">École destinataire</label>
                    @if($schools->count() > 1)
                        <select name="school_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Choisir une école --</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                    {{ $school->name }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($schools->count() == 1)
                        <input type="hidden" name="school_id" value="{{ $schools->first()->id }}">
                        <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700">
                            {{ $schools->first()->name }}
                        </div>
                    @else
                        <div class="text-red-600 text-sm">Aucune école disponible</div>
                    @endif
                </div>

                <!-- Sujet -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Sujet</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Ex: Question sur les frais de scolarité">
                </div>

                <!-- Message -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Message</label>
                    <textarea name="message" rows="8" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Votre message (minimum 10 caractères)...">{{ old('message') }}</textarea>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('parent.messages.index') }}" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-lg font-medium text-center hover:bg-gray-200 transition">
                        Annuler
                    </a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>