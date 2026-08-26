<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer mon école - SchoolManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
        }
    </style>
    @include('components.numeric-guard-script')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    <!-- Header simple -->
    <header class="bg-white shadow-sm py-4">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-green-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-white text-xl"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">School<span class="text-blue-600">Manager</span></span>
            </a>
            <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium">
                Déjà un compte ? <span class="text-blue-600 font-bold">Se connecter</span>
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="max-w-3xl w-full">
            
            <!-- Titre -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center bg-green-100 text-green-700 px-4 py-2 rounded-full mb-4">
                    <i class="fas fa-gift mr-2"></i>
                    <span class="font-semibold">Essai gratuit de 30 jours - Sans carte bancaire</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-2">Créez votre école en 2 minutes</h1>
                <p class="text-gray-600">Commencez à gérer votre établissement dès aujourd'hui.</p>
            </div>

            <!-- Erreurs de validation -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('school.register.submit') }}" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-xl overflow-hidden">
                @csrf

                <!-- ===== SECTION 1 : INFORMATIONS DE L'ÉCOLE ===== -->
                <div class="gradient-bg px-6 py-4">
                    <h2 class="text-white font-bold text-lg flex items-center">
                        <i class="fas fa-school mr-3"></i>
                        1. Informations de l'école
                    </h2>
                </div>

                <div class="p-6 md:p-8 space-y-5">
                    <div class="grid md:grid-cols-2 gap-5">
                        <!-- Nom de l'école -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Nom de l'école <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="school_name" value="{{ old('school_name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Ex: École Les Mirabelles">
                        </div>

                        <!-- Email de l'école -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Email de l'école <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="school_email" value="{{ old('school_email') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="contact@monécole.com">
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Téléphone <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="school_phone" value="{{ old('school_phone') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="+225 07 00 00 00 00">
                        </div>

                        <!-- Adresse -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Adresse <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="school_address" value="{{ old('school_address') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="Quartier, ville, pays">
                        </div>

                        <!-- Type d'école -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Type d'école <span class="text-red-500">*</span>
                            </label>
                            <select name="school_type" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="both" {{ old('school_type') == 'both' ? 'selected' : '' }}>Maternelle & Primaire</option>
                                <option value="maternelle" {{ old('school_type') == 'maternelle' ? 'selected' : '' }}>Maternelle uniquement</option>
                                <option value="primaire" {{ old('school_type') == 'primaire' ? 'selected' : '' }}>Primaire uniquement</option>
                            </select>
                        </div>

                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Logo de l'école <span class="text-gray-400 font-normal">(optionnel)</span>
                            </label>
                            <input type="file" name="school_logo" accept="image/*"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-semibold">
                        </div>
                    </div>
                </div>

                <!-- ===== SECTION 2 : COMPTE ADMINISTRATEUR ===== -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h2 class="text-white font-bold text-lg flex items-center">
                        <i class="fas fa-user-shield mr-3"></i>
                        2. Votre compte administrateur
                    </h2>
                </div>

                <div class="p-6 md:p-8 space-y-5">
                    <div class="grid md:grid-cols-2 gap-5">
                        <!-- Prénom -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Prénom <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="admin_first_name" value="{{ old('admin_first_name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                placeholder="Votre prénom">
                        </div>

                        <!-- Nom -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="admin_last_name" value="{{ old('admin_last_name') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                placeholder="Votre nom">
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Email (identifiant de connexion) <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                placeholder="votre@email.com">
                        </div>

                        <!-- Mot de passe -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Mot de passe <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                placeholder="8 caractères minimum">
                        </div>

                        <!-- Confirmation -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Confirmer le mot de passe <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password_confirmation" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                placeholder="Répétez le mot de passe">
                        </div>
                    </div>
                </div>

                <!-- ===== BOUTON DE SOUMISSION ===== -->
                <div class="px-6 md:px-8 pb-8">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-green-500 hover:from-blue-700 hover:to-green-600 text-white py-4 rounded-lg font-bold text-lg transition shadow-xl shadow-blue-600/30 flex items-center justify-center group">
                        <i class="fas fa-rocket mr-2 group-hover:animate-bounce"></i>
                        Créer mon école et démarrer l'essai gratuit
                    </button>
                    <p class="text-center text-sm text-gray-500 mt-4">
                        <i class="fas fa-lock mr-1"></i>
                        Vos données sont sécurisées. Aucune carte bancaire requise.
                    </p>
                </div>
            </form>
        </div>
    </main>

    <footer class="text-center py-6 text-sm text-gray-500">
        © 2026 SchoolManager. Tous droits réservés.
    </footer>
</body>
</html>