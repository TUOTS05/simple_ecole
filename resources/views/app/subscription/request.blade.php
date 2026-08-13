@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-center mb-2">Demande de création d'école et d'abonnement</h2>
    <p class="text-center text-gray-600 mb-8">Remplissez les informations de votre établissement. Le Super Admin validera votre demande, créera votre espace et vous enverra vos identifiants.</p>

    <form action="{{ route('app.subscription.request.store') }}" method="POST" class="space-y-6 bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        @csrf
        
        <!-- Informations de l'École -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">1. Informations de l'établissement</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'école *</label>
                    <input type="text" name="school_name" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ex: Groupe Scolaire Les Mirabelles">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone de l'école *</label>
                    <input type="tel" name="school_phone" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="+225 07...">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse complète *</label>
                    <input type="text" name="school_address" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Ville, Quartier, Rue...">
                </div>
            </div>
        </div>

        <!-- Informations du Directeur -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">2. Informations du Directeur(trice)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                    <input type="text" name="director_name" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email professionnel (sera votre identifiant) *</label>
                    <input type="email" name="director_email" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Choix du Plan -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">3. Choix de l'offre</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($plans as $plan)
                <label class="relative block p-4 border-2 rounded-xl cursor-pointer hover:border-primary transition {{ $plan->is_recommended ? 'border-primary bg-blue-50' : 'border-gray-200' }}">
                    <input type="radio" name="plan_id" value="{{ $plan->id }}" class="absolute top-4 right-4 w-5 h-5 text-primary" required>
                    <h4 class="text-lg font-bold">{{ $plan->name }}</h4>
                    <p class="text-xl font-bold text-primary my-1">{{ number_format($plan->yearly_price, 0, ',', ' ') }} <span class="text-sm text-gray-500">FCFA / an</span></p>
                    <ul class="text-sm text-gray-600 space-y-1 mt-2">
                        <li>✅ Max {{ $plan->max_students }} élèves</li>
                        <li>✅ Max {{ $plan->max_teachers }} enseignants</li>
                    </ul>
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="w-full bg-primary text-white font-bold py-4 rounded-lg hover:bg-primary-dark transition shadow-lg text-lg">
            📩 Envoyer ma demande de création d'école
        </button>
    </form>
</div>
@endsection