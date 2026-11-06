<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EcoleTUO')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('icons/log.jpg.jpeg') }}">

    <!-- Styles compilés (npm run build:css) : remplacent le CDN Tailwind, qui générait les styles dans
         le navigateur à chaque page et ralentissait fortement l'affichage sur mobile et réseau faible. -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    
    @stack('styles')
    @include('components.numeric-guard-script')
</head>
<body class="bg-gray-50">
    {{-- Barre de chargement : visible dès qu'on quitte la page (lien, formulaire), pour signaler
         que la page suivante arrive, même sur un réseau lent. --}}
    <div id="page-loader" class="fixed top-0 left-0 h-1 bg-primary-dark z-50 pointer-events-none" style="width: 0; opacity: 0; transition: width 8s cubic-bezier(.1,.7,.3,1), opacity .3s"></div>
    <script>
        (function () {
            const bar = document.getElementById('page-loader');
            let hideTimer;
            const start = () => {
                bar.style.opacity = '1';
                bar.style.width = '85%';
                clearTimeout(hideTimer);
                // Téléchargement ou fichier ouvert sans changer de page : la barre ne doit pas rester affichée
                hideTimer = setTimeout(stop, 10000);
            };
            const stop = () => { bar.style.transition = 'none'; bar.style.width = '0'; bar.style.opacity = '0'; requestAnimationFrame(() => bar.style.transition = 'width 8s cubic-bezier(.1,.7,.3,1), opacity .3s'); };
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (! link || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || link.target === '_blank' || link.hasAttribute('download')) return;
                const url = new URL(link.href, location.href);
                if (url.origin !== location.origin || url.hash && url.pathname === location.pathname) return;
                start();
            });
            document.addEventListener('submit', (e) => { if (! e.defaultPrevented && e.target.target !== '_blank') start(); });
            window.addEventListener('pageshow', stop);
        })();
    </script>
    <div class="flex h-screen overflow-hidden">
        
        
        <!-- BARRE LATÉRALE -->
        @include('components.sidebar')
        
        <!-- CONTENU PRINCIPAL -->
        <div class="flex-1 flex flex-col overflow-hidden">
            @include('components.trial-banner')
            
            <!-- EN-TÊTE -->
            @include('components.header')
            
            <!-- ZONE DE CONTENU -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-6">
                @yield('content')
            </main>
            
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>