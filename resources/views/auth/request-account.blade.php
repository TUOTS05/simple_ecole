<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de compte - Simple Ecole</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .input-error { border-color: #ef4444 !important; }
        .input-error:focus { ring-color: #ef4444 !important; border-color: #ef4444 !important; }
    </style>
    @include('components.numeric-guard-script')
</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen flex flex-col">

    <!-- En-tête minimaliste -->
    <div class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center space-x-2 text-gray-800 hover:text-blue-600 transition">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-green-500 rounded-lg flex items-center justify-center shadow-md">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <span class="text-xl font-bold tracking-tight">Simple <span class="text-blue-600">Ecole</span></span>
            </a>
            <a href="{{ url('/') }}" class="text-sm font-medium text-gray-500 hover:text-blue-600 flex items-center transition">
                <i class="fas fa-arrow-left mr-2"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <!-- Contenu Principal -->
    <div class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-5 gap-8">
            
            <!-- Colonne Gauche : Arguments de vente (Trust signals) -->
            <div class="lg:col-span-2 space-y-6 pt-4">
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                    Rejoignez les écoles qui digitalisent leur gestion.
                </h2>
                <p class="text-gray-600">
                    Remplissez ce formulaire. Notre équipe crée votre espace, configure vos classes et vous envoie vos identifiants sous 24h.
                </p>
                
                <div class="space-y-4 mt-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mt-1">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-gray-900">Configuration gratuite</h4>
                            <p class="text-sm text-gray-500">Nous importons vos données initiales pour vous.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mt-1">
                            <i class="fas fa-shield-alt text-blue-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-gray-900">Données 100% sécurisées</h4>
                            <p class="text-sm text-gray-500">Hébergement sécurisé et sauvegardes quotidiennes.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mt-1">
                            <i class="fas fa-headset text-purple-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-semibold text-gray-900">Support dédié</h4>
                            <p class="text-sm text-gray-500">Une équipe à votre écoute par téléphone et WhatsApp.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Le Formulaire -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 sm:p-8">
                    
                    <!-- Affichage des erreurs de validation (CRUCIAL pour le debug) -->
                    @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Veuillez corriger les erreurs suivantes :</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('request-account.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- 1. Établissement -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-2 text-xs">1</span>
                                Votre Établissement
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'école *</label>
                                    <input type="text" name="school_name" value="{{ old('school_name') }}" required 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('school_name') input-error @enderror">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone *</label>
                                    <input type="tel" name="school_phone" value="{{ old('school_phone') }}" required 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('school_phone') input-error @enderror" placeholder="+225 07...">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville / Adresse *</label>
                                    <input type="text" name="school_address" value="{{ old('school_address') }}" required 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('school_address') input-error @enderror">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- 2. Responsable -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-2 text-xs">2</span>
                                Le Responsable
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                                    <input type="text" name="director_name" value="{{ old('director_name') }}" required 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('director_name') input-error @enderror">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email professionnel *</label>
                                    <input type="email" name="director_email" value="{{ old('director_email') }}" required 
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('director_email') input-error @enderror" placeholder="directeur@ecole.com">
                                    <p class="text-xs text-gray-500 mt-1">Cet email sera votre identifiant de connexion.</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- 3. Offre -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                                <span class="w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-2 text-xs">3</span>
                                Offre souhaitée
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @forelse($plans as $plan)
                                <label class="relative block p-4 border-2 rounded-xl cursor-pointer hover:border-blue-500 transition {{ ($plan->is_recommended ?? false) ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200' }}">
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" class="absolute top-4 right-4 w-5 h-5 text-blue-600" required {{ old('plan_id') == $plan->id ? 'checked' : '' }}>
                                    <h4 class="text-base font-bold text-gray-900">{{ $plan->name }}</h4>
                                    <p class="text-lg font-bold text-blue-600 my-1">{{ number_format($plan->yearly_price ?? 0, 0, ',', ' ') }} <span class="text-xs text-gray-500">FCFA/an</span></p>
                                    <p class="text-xs text-gray-500 -mt-1 mb-2">soit {{ number_format($plan->monthly_price ?? 0, 0, ',', ' ') }} FCFA/mois</p>
                                    <ul class="text-xs text-gray-600 space-y-1 mt-2">
                                        <li><i class="fas fa-check text-green-500 mr-1"></i> Max {{ $plan->max_students ?? 'Illimité' }} élèves</li>
                                        <li><i class="fas fa-check text-green-500 mr-1"></i> Max {{ $plan->max_users ?? 'Illimité' }} utilisateurs</li>
                                    </ul>
                                </label>
                                @empty
                                <div class="sm:col-span-2 text-center py-4 text-gray-500 bg-gray-50 rounded-lg">
                                    Aucun plan d'abonnement n'est actuellement configuré.
                                </div>
                                @endforelse
                            </div>
                            @error('plan_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Bouton -->
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-600/20 text-base flex items-center justify-center group">
                                <i class="fas fa-paper-plane mr-2 group-hover:translate-x-1 transition-transform"></i>
                                Envoyer ma demande de création
                            </button>
                            <p class="text-center text-xs text-gray-500 mt-4 flex items-center justify-center">
                                <i class="fas fa-lock mr-1.5"></i> Vos données sont chiffrées. Aucun paiement n'est requis à cette étape.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>