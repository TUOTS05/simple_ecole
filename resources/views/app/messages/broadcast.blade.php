@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">✉️ Nouveau Message Groupé</h2>
        <a href="{{ route('app.messages.index') }}" class="text-sm text-gray-500 hover:text-primary flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour à la boîte de réception
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg border border-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200 flex items-center">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('app.messages.broadcast.store') }}" method="POST" x-data="{ targetType: 'all' }">
        @csrf

        <!-- Sélection du destinataire -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Destinataires</label>
            <select name="target_type" x-model="targetType" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                <option value="all">📢 Tous les parents de l'école</option>
                <option value="class">🏫 Une classe spécifique</option>
                <option value="parent">👤 Un parent spécifique</option>
            </select>
        </div>

        <!-- Champ dynamique : Classe -->
        <div x-show="targetType === 'class'" class="mb-6" x-transition>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionner la classe</label>
            <select name="target_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Champ dynamique : Parent spécifique -->
        <div x-show="targetType === 'parent'" class="mb-6" x-transition>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionner le parent</label>
            <select name="receiver_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                <option value="">-- Choisir un parent --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->display_name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Objet du message -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Objet</label>
            <input type="text" name="subject" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border" placeholder="Ex: Réunion de rentrée, Changement d'horaires...">
        </div>

        <!-- Corps du message -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
            <textarea name="message" rows="6" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border" placeholder="Rédigez votre message ici..."></textarea>
        </div>

        <!-- Boutons d'action -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('app.messages.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Annuler</a>
            <button type="submit" class="px-6 py-2 bg-primary text-white font-medium rounded-md hover:bg-primary/90 transition-colors shadow-sm flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                Envoyer le message
            </button>
        </div>
    </form>
</div>
@endsection