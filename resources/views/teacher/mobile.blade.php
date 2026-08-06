<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Espace Enseignant')</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#87CEEB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="EcoleTUO">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#87CEEB',
                        'primary-dark': '#5FB3D9',
                        'secondary': '#FFF9C4',
                        'accent': '#A5D6A7',
                        'danger': '#EF9A9A',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { -webkit-tap-highlight-color: transparent; overscroll-behavior: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .page-transition { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Mobile : Padding pour la nav du bas */
        .content-with-nav { padding-bottom: 80px; }
        
        /* Web : Ajustement pour le layout avec sidebar */
        @media (min-width: 768px) {
            .content-with-nav { padding-bottom: 0; }
        }
    </style>
</head>

<body class="bg-gray-100 md:flex md:h-screen md:overflow-hidden">

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SIDEBAR (Visible UNIQUEMENT sur Web / Desktop) -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <aside class="hidden md:flex md:flex-col md:w-64 md:bg-white md:shadow-xl md:z-40">
        <div class="h-16 flex items-center justify-center border-b border-gray-200">
            <h1 class="text-xl font-bold text-primary tracking-tight">Espace Enseignant</h1>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route('teacher.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('teacher.dashboard') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-home w-6 text-center mr-3"></i> <span>Tableau de bord</span>
            </a>
            <a href="{{ route('teacher.classes') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('teacher.classes') || request()->routeIs('teacher.class.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-chalkboard w-6 text-center mr-3"></i> <span>Mes Classes</span>
            </a>
            <a href="{{ route('teacher.attendance.index') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('teacher.attendance.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                <i class="fas fa-clipboard-check w-6 text-center mr-3"></i> <span>Faire l'appel</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-200">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 hover:bg-red-50 hover:text-red-600 transition text-gray-700 text-sm font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                </button>
            </form>
        </div>
    </aside>

        <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- HEADER (Visible sur Mobile ET Web) -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <header class="bg-primary text-white shadow-md sticky top-0 z-40 flex-shrink-0">
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Bouton retour (Visible UNIQUEMENT sur mobile) -->
                <button onclick="history.back()" class="md:hidden text-white text-xl hover:text-gray-200 transition">
                    <i class="fas fa-arrow-left"></i>
                </button>
                
                <div>
                    <!-- Le titre vient de @yield('page_title') dans la vue -->
                    <h1 class="text-lg font-bold">@yield('page_title', 'Espace Enseignant')</h1>
                    <!-- Sous-titre (Visible UNIQUEMENT sur Web) -->
                    <p class="text-xs text-blue-100 hidden md:block">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </p>
                </div>
            </div>
            
            <!-- Bouton Déconnexion rapide (Visible UNIQUEMENT sur mobile) -->
            <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                @csrf
                <button type="submit" class="text-white hover:text-gray-200 transition p-2" title="Déconnexion">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </button>
            </form>
        </div>
    </header> <!-- ✅ ICI : Le ">" était manquant dans la version précédente -->
    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(console.error);
            });
        }
    </script>
</body>
</html>