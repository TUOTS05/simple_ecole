<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Ecole - La Solution Complète de Gestion Scolaire</title>
    <meta name="description" content="Gérez votre école de A à Z : inscriptions, paiements, bulletins, présences, cartes scolaires QR code. Une plateforme moderne pour les écoles d'Afrique.">
    <link rel="icon" type="image/jpeg" href="{{ asset('icons/log.jpg.jpeg') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
            }

            50% {
                box-shadow: 0 0 40px rgba(37, 99, 235, 0.6);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .delay-100 {
            animation-delay: 0.1s;
            opacity: 0;
        }

        .delay-200 {
            animation-delay: 0.2s;
            opacity: 0;
        }

        .delay-300 {
            animation-delay: 0.3s;
            opacity: 0;
        }

        .delay-400 {
            animation-delay: 0.4s;
            opacity: 0;
        }

        /* Gradient backgrounds */
        .gradient-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #2563eb, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glass effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Card hover */
        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Mobile menu */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #2563eb;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-white text-gray-800">

    <!-- ============ NAVIGATION ============ -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-green-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Simple <span class="text-blue-600">Ecole</span></span>
                </div>

                <!-- Menu Desktop -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-blue-600 transition font-medium">Fonctionnalités</a>
                    <a href="#spaces" class="text-gray-600 hover:text-blue-600 transition font-medium">Espaces</a>
                    <a href="#pricing" class="text-gray-600 hover:text-blue-600 transition font-medium">Tarifs</a>
                    <a href="#contact" class="text-gray-600 hover:text-blue-600 transition font-medium">Contact</a>
                </div>

                <!-- CTA -->
                <div class="hidden md:flex items-center space-x-3">
                    <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition">Connexion</a>
                    <a href="{{ route('demo.login') }}" class="relative group bg-white text-blue-600 hover:bg-gray-100 px-6 py-3 rounded-lg font-bold transition shadow-lg flex items-center justify-center">
                        <i class="fas fa-play-circle mr-2"></i>
                        Démo en direct
                        <!-- Badge explicatif -->
                        <span class="absolute -top-2 -right-2 bg-yellow-400 text-yellow-900 text-[10px] font-black px-2 py-0.5 rounded-full shadow-sm border border-white">
                            SANDBOX
                        </span>
                    </a>
                    <a href="{{ route('request-account') }}" class="bg-green-500 hover:bg-green-600 text-white px-8 py-4 rounded-lg font-bold transition shadow-lg flex items-center justify-center animate-pulse-glow">
                        <i class="fas fa-building mr-2"></i>
                        Demander mon compte
                    </a>
                </div>
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu fixed top-0 right-0 h-full w-64 bg-white shadow-2xl md:hidden z-50">
            <div class="p-6">
                <button id="close-menu" class="absolute top-4 right-4 text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <!-- Menu Mobile -->
                <div class="mt-12 flex flex-col space-y-4">
                    <a href="#features" class="text-gray-700 font-medium py-2">Fonctionnalités</a>
                    <a href="#spaces" class="text-gray-700 font-medium py-2">Espaces</a>
                    <a href="#pricing" class="text-gray-700 font-medium py-2">Tarifs</a>
                    <a href="#contact" class="text-gray-700 font-medium py-2">Contact</a>
                    <hr class="my-4">
                    <a href="/login" class="text-gray-700 font-medium py-2">Connexion</a>
                    <a href="{{ route('request-account') }}" target="_blank" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2.5 rounded-lg font-medium transition shadow-lg shadow-green-500/30">
                        Demander mon compte
                    </a>

                </div>
            </div>
        </div>
    </nav>

    <!-- ============ HERO SECTION ============ -->
    <section class="gradient-hero pt-24 pb-20 md:pt-32 md:pb-32 relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-green-300 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Text -->
                <div class="text-white">
                    <div class="inline-flex items-center bg-white/20 backdrop-blur-md px-4 py-2 rounded-full mb-6 animate-fade-in-up">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-sm font-medium">Nouveau : Cartes scolaires avec QR Code 🎉</span>
                    </div>

                    <h1 class="text-4xl md:text-6xl font-black leading-tight mb-6 animate-fade-in-up delay-100">
                        Gérez votre école <br>
                        <span class="text-green-300">de A à Z</span>, simplement.
                    </h1>

                    <p class="text-lg md:text-xl text-blue-100 mb-8 leading-relaxed animate-fade-in-up delay-200">
                        La plateforme tout-en-un conçue pour les écoles d'Afrique.
                        Inscriptions, paiements, bulletins, présences, cartes scolaires...
                        <strong>Tout est centralisé, sécurisé et accessible partout.</strong>
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 animate-fade-in-up delay-300">
                        <a href="{{ route('request-account') }}" class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 rounded-lg font-bold transition shadow-2xl flex items-center justify-center group">
                            Démarrer ma demande
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition"></i>
                        </a>
                        <a href="#demo" class="glass text-white hover:bg-white/20 px-8 py-4 rounded-lg font-bold transition flex items-center justify-center">
                            <i class="fas fa-play-circle mr-2"></i>
                            Voir la démo
                        </a>
                    </div>

                    <!-- Social proof -->
                    <div class="mt-10 flex items-center space-x-6 animate-fade-in-up delay-400">
                        <div class="flex -space-x-2">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-400 to-red-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">AK</div>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">MS</div>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-teal-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">FD</div>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-indigo-500 border-2 border-white flex items-center justify-center text-white text-xs font-bold">+50</div>
                        </div>
                        <div>
                            <div class="flex text-yellow-300 text-sm">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <p class="text-blue-100 text-sm">+50 écoles nous font confiance</p>
                        </div>
                    </div>
                </div>

                <!-- Visual -->
                <div class="relative animate-float">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 transform rotate-2 hover:rotate-0 transition duration-500">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            </div>
                            <span class="text-xs text-gray-400">Dashboard Admin</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-gray-500">Élèves inscrits</p>
                                        <p class="text-2xl font-bold text-gray-900">1 247</p>
                                    </div>
                                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user-graduate text-white"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-green-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500">Recouvrement</p>
                                    <p class="text-lg font-bold text-green-600">87.5%</p>
                                </div>
                                <div class="bg-purple-50 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500">Présences</p>
                                    <p class="text-lg font-bold text-purple-600">94.2%</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-file-pdf text-white text-xs"></i>
                                    </div>
                                    <span class="text-sm text-gray-700">Bulletins générés</span>
                                </div>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">+24 aujourd'hui</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ STATS SECTION ============ -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center reveal">
                    <div class="text-4xl md:text-5xl font-black gradient-text mb-2">50+</div>
                    <p class="text-gray-600 font-medium">Écoles équipées</p>
                </div>
                <div class="text-center reveal">
                    <div class="text-4xl md:text-5xl font-black gradient-text mb-2">15K+</div>
                    <p class="text-gray-600 font-medium">Élèves gérés</p>
                </div>
                <div class="text-center reveal">
                    <div class="text-4xl md:text-5xl font-black gradient-text mb-2">99.9%</div>
                    <p class="text-gray-600 font-medium">Disponibilité</p>
                </div>
                <div class="text-center reveal">
                    <div class="text-4xl md:text-5xl font-black gradient-text mb-2">24/7</div>
                    <p class="text-gray-600 font-medium">Support client</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ FEATURES SECTION ============ -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-blue-100 text-blue-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    ✨ FONCTIONNALITÉS
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-4">
                    Tout ce dont votre école a <span class="gradient-text">besoin</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Une suite complète d'outils pensés pour simplifier la gestion quotidienne de votre établissement.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-user-plus text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Inscriptions & Élèves</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Gérez les inscriptions, matricules automatiques, photos, et dossiers complets des élèves en quelques clics.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Paiements & Échéances</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Suivez les paiements, générez automatiquement les échéances, et visualisez les impayés par classe en temps réel.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-file-alt text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Bulletins Scolaires</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Saisie des notes, calcul automatique des moyennes, mentions, et génération PDF officielle en un clic.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-calendar-check text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Présences & Absences</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Appel quotidien matin/après-midi, rapports détaillés, alertes automatiques aux parents en cas d'absence.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-qrcode text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Cartes Scolaires QR</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Génération automatique de cartes d'identité scolaires avec QR code après le premier paiement d'inscription.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-chart-line text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Rapports & Statistiques</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Tableaux de bord en temps réel, exports PDF/Excel, analyses financières et pédagogiques détaillées.
                    </p>
                </div>

                <!-- Feature 7 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-pink-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-users text-pink-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Espace Parents</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Les parents consultent notes, présences, paiements et bulletins de tous leurs enfants depuis une seule interface.
                    </p>
                </div>

                <!-- Feature 8 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-teal-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-chalkboard-teacher text-teal-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Espace Enseignants</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Saisie des notes et présences, consultation des classes assignées, interface mobile optimisée.
                    </p>
                </div>

                <!-- Feature 9 -->
                <div class="feature-card bg-white p-8 rounded-2xl border border-gray-100 reveal">
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mb-5">
                        <i class="fas fa-building text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Multi-Écoles</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Gérez plusieurs établissements depuis un seul compte. Données isolées, logo personnalisé pour chaque école.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ DEMO VIDEO SECTION ============ -->
    <section id="demo" class="py-20 bg-gradient-to-br from-blue-50 via-white to-green-50 relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-green-500 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-12 reveal">
                <span class="inline-block bg-red-100 text-red-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    🎥 DÉMONSTRATION
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-4">
                    Voyez <span class="gradient-text">Simple Ecole</span> en action
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Pas de vidéo à regarder : explorez directement l'interface avec de vraies données de démonstration.
                </p>
            </div>

            <!-- Live Demo CTA (remplace l'ancienne fausse vidéo) -->
            <div class="max-w-5xl mx-auto reveal">
                <div class="relative rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-blue-600 to-blue-800 p-10 md:p-16 text-center">
                    <div class="absolute top-10 left-10 w-20 h-20 border-2 border-white/20 rounded-lg transform rotate-12"></div>
                    <div class="absolute bottom-10 right-10 w-16 h-16 border-2 border-white/20 rounded-full"></div>
                    <div class="absolute top-1/2 left-1/4 w-12 h-12 bg-white/10 rounded-lg transform -rotate-12"></div>

                    <div class="relative w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse-glow">
                        <i class="fas fa-play text-3xl text-white ml-1"></i>
                    </div>
                    <h3 class="relative text-2xl md:text-3xl font-bold text-white mb-3">Essayez la démo en direct</h3>
                    <p class="relative text-blue-100 max-w-xl mx-auto mb-8">
                        Un compte de démonstration partagé, en lecture seule, réinitialisé chaque nuit. Aucune inscription requise.
                    </p>
                    <a href="{{ route('demo.login') }}" class="relative inline-flex items-center bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 rounded-lg font-bold transition shadow-xl">
                        <i class="fas fa-play-circle mr-2"></i>
                        Ouvrir la démo sandbox
                    </a>
                </div>
            </div>

            <!-- Alternative: Screenshots Carousel -->
            <div class="mt-16 reveal">
                <h3 class="text-2xl font-bold text-center text-gray-900 mb-8">
                    Aperçu de l'interface
                </h3>

                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Screenshot 1 -->
                    <div class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition">
                        <div class="aspect-video overflow-hidden">
                            <img src="{{ asset('screenshots/dashboard.jpg') }}" alt="Tableau de bord Simple Ecole" class="w-full h-full object-cover object-top">
                        </div>
                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <div class="text-white text-center p-4">
                                <i class="fas fa-search-plus text-3xl mb-2"></i>
                                <p class="font-bold">Vue d'ensemble en temps réel</p>
                            </div>
                        </div>
                    </div>

                    <!-- Screenshot 2 -->
                    <div class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition">
                        <div class="aspect-video overflow-hidden">
                            <img src="{{ asset('screenshots/payments.jpg') }}" alt="Gestion des paiements et inscriptions" class="w-full h-full object-cover object-top">
                        </div>
                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <div class="text-white text-center p-4">
                                <i class="fas fa-search-plus text-3xl mb-2"></i>
                                <p class="font-bold">Suivi des échéances et impayés</p>
                            </div>
                        </div>
                    </div>

                    <!-- Screenshot 3 -->
                    <div class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition">
                        <div class="aspect-video overflow-hidden">
                            <img src="{{ asset('screenshots/reports.jpg') }}" alt="Rapports financiers et statistiques" class="w-full h-full object-cover object-top">
                        </div>
                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <div class="text-white text-center p-4">
                                <i class="fas fa-search-plus text-3xl mb-2"></i>
                                <p class="font-bold">Analyses détaillées PDF/Excel</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA after demo -->
            <div class="mt-12 text-center reveal">
                <p class="text-lg text-gray-700 mb-6">
                    Convaincu ? Envoyez votre demande dès maintenant !
                </p>
                <a href="{{ route('request-account') }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-bold transition shadow-xl shadow-blue-600/30 group">
                    <i class="fas fa-rocket mr-2 group-hover:animate-bounce"></i>
                    Demander mon compte
                </a>
            </div>
        </div>
    </section>

    <!-- ============ SPACES SECTION ============ -->
    <section id="spaces" class="py-20 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-green-100 text-green-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    👥 ESPACES DÉDIÉS
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-4">
                    Chaque rôle a son <span class="gradient-text">espace dédié</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Une interface adaptée à chaque utilisateur pour une expérience fluide et efficace.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Admin -->
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition reveal">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-user-shield text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Administrateur</h3>
                    <p class="text-gray-600 mb-6">Contrôle total de l'établissement</p>
                    <ul class="space-y-3">
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Gestion complète des élèves et classes</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Suivi financier et rapports</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Configuration des frais et échéances</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Gestion des utilisateurs et rôles</span></li>
                    </ul>
                </div>

                <!-- Enseignant -->
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition reveal">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Enseignant</h3>
                    <p class="text-gray-600 mb-6">Outils pédagogiques simplifiés</p>
                    <ul class="space-y-3">
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Saisie rapide des notes</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Appel des présences quotidien</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Consultation des classes assignées</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Exports de rapports de classe</span></li>
                    </ul>
                </div>

                <!-- Parent -->
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition reveal">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-user-friends text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Parent</h3>
                    <p class="text-gray-600 mb-6">Suivi complet de la scolarité</p>
                    <ul class="space-y-3">
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Consultation des bulletins PDF</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Historique des présences</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Suivi des paiements et reçus</span></li>
                        <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i><span class="text-gray-700">Multi-enfants sur un seul compte</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ PRICING SECTION ============ -->
    <section id="pricing" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-purple-100 text-purple-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    💰 TARIFS
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-4">
                    Des tarifs <span class="gradient-text">adaptés</span> à votre école
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Choisissez la formule qui correspond à la taille de votre établissement.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="bg-white rounded-2xl p-8 border-2 border-gray-100 hover:border-blue-200 transition reveal">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Starter</h3>
                    <p class="text-gray-500 mb-6">Pour les petites écoles</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-gray-900">25 000</span>
                        <span class="text-gray-500"> FCFA/mois</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Jusqu'à 200 élèves</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>3 utilisateurs</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Toutes les fonctionnalités</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Support email</li>
                    </ul>
                    <a href="{{ route('request-account') }}" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 rounded-lg font-bold transition">
                        Commencer
                    </a>
                </div>

                <!-- Pro (Recommandé) -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-8 text-white relative transform md:scale-105 shadow-2xl reveal">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-sm font-bold">
                        ⭐ POPULAIRE
                    </div>
                    <h3 class="text-xl font-bold mb-2">Medium</h3>
                    <p class="text-blue-100 mb-6">Pour les écoles en croissance</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black">50 000</span>
                        <span class="text-blue-100"> FCFA/mois</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center"><i class="fas fa-check text-green-300 mr-2"></i>Jusqu'à 500 élèves</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-300 mr-2"></i>05 utilisateurs</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-300 mr-2"></i>Toutes les fonctionnalités</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-300 mr-2"></i>Support prioritaire 24/7</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-300 mr-2"></i>Formation incluse</li>
                    </ul>
                    <a href="{{ route('request-account') }}" class="block w-full text-center bg-white hover:bg-gray-100 text-blue-600 py-3 rounded-lg font-bold transition">
                        Choisir ce plan
                    </a>
                </div>

                <!-- Enterprise -->
                <div class="bg-white rounded-2xl p-8 border-2 border-gray-100 hover:border-blue-200 transition reveal">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Premium</h3>
                    <p class="text-gray-500 mb-6">Pour les grands groupes</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-gray-900">Sur devis</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Élèves illimités</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Utilisateurs illimités</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Multi-écoles</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Support dédié</li>
                        <li class="flex items-center text-gray-700"><i class="fas fa-check text-green-500 mr-2"></i>Personnalisation avancée</li>
                    </ul>
                    <a href="#contact" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 rounded-lg font-bold transition">
                        Nous contacter pour Premium
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TESTIMONIALS ============ -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-yellow-100 text-yellow-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    💬 TÉMOIGNAGES
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 mb-4">
                    Ils nous font <span class="gradient-text">confiance</span>
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-lg reveal">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">
                        "Simple Ecole a transformé la gestion de notre école. Les bulletins sont générés en quelques minutes, et les parents adorent pouvoir suivre les notes en ligne."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                            AK
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Mme Kouassi</p>
                            <p class="text-sm text-gray-500">Directrice, École Les Mirabelles</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-lg reveal">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">
                        "Le suivi des paiements est devenu un jeu d'enfant. On voit immédiatement les impayés par classe, et les reçus PDF sont très professionnels."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                            SD
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">M. Diarra</p>
                            <p class="text-sm text-gray-500">Comptable, Groupe Scolaire Excellence</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-lg reveal">
                    <div class="flex text-yellow-400 mb-4">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">
                        "En tant que parent, j'apprécie pouvoir consulter les bulletins et les présences de mes deux enfants depuis mon téléphone. Interface simple et claire."
                    </p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                            FB
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Mme Bernard</p>
                            <p class="text-sm text-gray-500">Parent d'élèves</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ CTA SECTION ============ -->
    <section class="py-20 gradient-hero relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-green-300 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <h2 class="text-3xl md:text-5xl font-black text-white mb-6 reveal">
                Prêt à moderniser votre école ?
            </h2>
            <p class="text-xl text-blue-100 mb-8 reveal">
                Rejoignez plus de 50 écoles qui ont déjà fait le choix de la digitalisation.
                <br>Réponse et configuration de votre espace sous 24h.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center reveal">
                <a href="{{ route('request-account') }}" class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 rounded-lg font-bold transition shadow-2xl">
                    <i class="fas fa-rocket mr-2"></i>
                    Démarrer ma demande
                </a>
                <a href="#contact" class="glass text-white hover:bg-white/20 px-8 py-4 rounded-lg font-bold transition">
                    <i class="fas fa-phone mr-2"></i>
                    Parler à un expert
                </a>
            </div>
        </div>
    </section>

    <!-- ============ DEMANDE DE CONTRAT / DÉMO ============ -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-8 md:p-12 shadow-2xl text-center reveal">
                <h2 class="text-3xl md:text-4xl font-black text-white mb-4">
                    Prêt à tester avec vos propres données ?
                </h2>
                <p class="text-blue-100 mb-8 text-lg">
                    Envoyez votre demande. Notre équipe configure votre espace et valide votre abonnement sous 24h.
                </p>

                <div class="max-w-md mx-auto space-y-4">
                    <a href="{{ route('request-account') }}" class="block w-full text-center bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-lg transition shadow-lg transform hover:-translate-y-1">
                        <i class="fas fa-paper-plane mr-2"></i> Demander mon compte
                    </a>
                    <p class="text-xs text-blue-200 mt-3">
                        🔒 Aucune carte de crédit requise en ligne. Un conseiller vous contacte pour finaliser votre abonnement.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ FOOTER ============ -->
    <footer id="contact" class="bg-gray-900 text-gray-300 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <!-- Brand -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-green-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-white">Simple <span class="text-blue-400">Ecole</span></span>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">
                        La solution complète de gestion scolaire pour les écoles d'Afrique.
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-9 h-9 bg-gray-800 hover:bg-blue-600 rounded-lg flex items-center justify-center transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-gray-800 hover:bg-blue-400 rounded-lg flex items-center justify-center transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-gray-800 hover:bg-pink-600 rounded-lg flex items-center justify-center transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-9 h-9 bg-gray-800 hover:bg-blue-700 rounded-lg flex items-center justify-center transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="text-white font-bold mb-4">Produit</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="#" class="hover:text-white transition">Sécurité</a></li>
                        <li><a href="#" class="hover:text-white transition">Mises à jour</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-white font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Centre d'aide</a></li>
                        <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition">Statut du service</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white font-bold mb-4">Contact</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-blue-400 mt-1 mr-2"></i>
                            <span>contact@simple-ecole.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone text-blue-400 mt-1 mr-2"></i>
                            <span>+225 07 00 00 00 00</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-400 mt-1 mr-2"></i>
                            <span>Abidjan, Côte d'Ivoire</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-400">
                    © 2026 Simple Ecole. Tous droits réservés.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0 text-sm">
                    <a href="#" class="hover:text-white transition">Conditions d'utilisation</a>
                    <a href="#" class="hover:text-white transition">Politique de confidentialité</a>
                    <a href="#" class="hover:text-white transition">Mentions légales</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============ SCRIPTS ============ -->
    <script>
        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeMenu = document.getElementById('close-menu');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => mobileMenu.classList.add('open'));
        closeMenu.addEventListener('click', () => mobileMenu.classList.remove('open'));

        // Scroll reveal
        const reveals = document.querySelectorAll('.reveal');
        const revealOnScroll = () => {
            reveals.forEach(el => {
                const top = el.getBoundingClientRect().top;
                if (top < window.innerHeight - 100) {
                    el.classList.add('active');
                }
            });
        };
        window.addEventListener('scroll', revealOnScroll);
        revealOnScroll();

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    mobileMenu.classList.remove('open');
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                nav.classList.add('shadow-md');
            } else {
                nav.classList.remove('shadow-md');
            }
        });


    </script>
</body>

</html>